<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plans\ParseCsvRequest;
use App\Http\Requests\Plans\SaveMatchPlanRequest;
use App\Models\ClubMatch;
use App\Models\Team;
use App\Services\Notifications\ScheduleUpdateNotifier;
use App\Services\Plans\PlanCsvParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MatchPlanController extends Controller
{
    public function __construct(
        private readonly PlanCsvParser $csvParser,
        private readonly ScheduleUpdateNotifier $scheduleUpdateNotifier,
    ) {}

    public function show(Request $request): Response
    {
        $user = $request->user();
        $visibleTeams = $user->clubTeamsForPlans();
        $manageableTeamIds = $user->manageableTeamsForMatchPlans()->pluck('id')->all();
        $tenantId = $visibleTeams->first()?->tenant_id;
        $selectedSeason = $request->string('season')->toString();

        $seasons = $tenantId === null
            ? collect()
            : ClubMatch::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('team_id', $visibleTeams->pluck('id'))
                ->select('season')
                ->distinct()
                ->orderByDesc('season')
                ->pluck('season');

        $defaultSeason = $selectedSeason !== ''
            ? $selectedSeason
            : $this->defaultSeason($tenantId, $visibleTeams->pluck('id')->all(), $seasons->all());

        $suggestions = [
            'opponents' => $tenantId === null
                ? collect()
                : ClubMatch::query()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('team_id', $visibleTeams->pluck('id'))
                    ->whereNotNull('versus')
                    ->where('versus', '<>', '')
                    ->distinct()
                    ->orderBy('versus')
                    ->pluck('versus')
                    ->values(),
            'cities' => $tenantId === null
                ? collect()
                : ClubMatch::query()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('team_id', $visibleTeams->pluck('id'))
                    ->whereNotNull('city')
                    ->where('city', '<>', '')
                    ->distinct()
                    ->orderBy('city')
                    ->pluck('city')
                    ->values(),
        ];

        $matches = $tenantId === null || $defaultSeason === null
            ? collect()
            : ClubMatch::query()
                ->select(['id', 'team_id', 'season', 'date', 'versus', 'at_home', 'city'])
                ->where('tenant_id', $tenantId)
                ->where('season', $defaultSeason)
                ->whereIn('team_id', $visibleTeams->pluck('id'))
                ->orderBy('date')
                ->orderBy('id')
                ->get()
                ->map(fn (ClubMatch $match) => [
                    'id' => $match->id,
                    'team_id' => $match->team_id,
                    'season' => $match->season,
                    'date' => $match->date->format('Y-m-d'),
                    'kickoff_time' => null,
                    'versus' => $match->versus,
                    'at_home' => $match->at_home,
                    'city' => $match->city,
                ]);

        return Inertia::render('Spielplan', [
            'teams' => $visibleTeams->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'canEdit' => in_array($team->id, $manageableTeamIds, true),
            ])->values(),
            'seasons' => $seasons->values(),
            'season' => $defaultSeason,
            'matches' => $matches->values(),
            'suggestions' => $suggestions,
            'canEdit' => count($manageableTeamIds) > 0,
        ]);
    }

    public function parseCsv(ParseCsvRequest $request): JsonResponse
    {
        $rows = $this->csvParser->readRows($request->file('file'));
        $errors = [];
        $parsedRows = [];
        $names = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_line'] ?? 0);
            $dateValue = $row['datum'] ?? null;
            $date = $this->csvParser->parseDate(is_string($dateValue) ? $dateValue : null);
            $season = trim((string) ($row['saison'] ?? ''));
            $home = trim((string) ($row['mannschaft_1'] ?? ''));
            $away = trim((string) ($row['mannschaft_2'] ?? ''));
            $city = trim((string) ($row['ort'] ?? ''));
            $kickoffValue = $row['uhrzeit'] ?? null;
            $kickoff = $this->csvParser->parseKickoffTime(is_string($kickoffValue) ? $kickoffValue : null);

            if ($date === null || $season === '' || $home === '' || $away === '') {
                $errors[] = [
                    'line' => $line,
                    'message' => 'Pflichtfelder fehlen oder sind ungültig.',
                ];

                continue;
            }

            if (($row['uhrzeit'] ?? '') !== '' && $kickoff === null) {
                $errors[] = [
                    'line' => $line,
                    'message' => 'Ungültige Uhrzeit.',
                ];

                continue;
            }

            $names[$home] = true;
            $names[$away] = true;

            $parsedRows[] = [
                'date' => $date,
                'kickoff_time' => $kickoff,
                'home' => $home,
                'away' => $away,
                'city' => $city !== '' ? $city : null,
                'season' => $season,
            ];
        }

        $manageableTeams = $request->user()->manageableTeamsForMatchPlans();
        $nameCandidates = array_keys($names);
        sort($nameCandidates, SORT_NATURAL | SORT_FLAG_CASE);

        $suggested = $manageableTeams->mapWithKeys(function (Team $team) use ($nameCandidates): array {
            $teamName = $this->csvParser->normalizeComparableName($team->name);

            foreach ($nameCandidates as $candidate) {
                if ($this->csvParser->normalizeComparableName($candidate) === $teamName) {
                    return [$team->id => $candidate];
                }
            }

            return [$team->id => null];
        });

        return response()->json([
            'rows' => $parsedRows,
            'names' => $nameCandidates,
            'suggested' => $suggested,
            'errors' => $errors,
        ]);
    }

    public function sync(SaveMatchPlanRequest $request): RedirectResponse
    {
        $user = $request->user();
        $teamId = $request->integer('team_id');
        $season = $request->string('season')->toString();
        $manageableTeam = $user->manageableTeamsForMatchPlans()->firstWhere('id', $teamId);

        if ($manageableTeam === null) {
            abort(403);
        }

        DB::transaction(function () use ($request, $manageableTeam, $season): void {
            $rows = $request->validated('rows');

            if (! is_array($rows)) {
                $rows = [];
            }

            $existingMatches = ClubMatch::query()
                ->where('team_id', $manageableTeam->id)
                ->where('season', $season)
                ->get()
                ->keyBy('id');

            $incomingIds = collect($rows)
                ->pluck('id')
                ->filter(fn ($id) => $id !== null)
                ->map(fn ($id) => (int) $id)
                ->all();
            $hasChanges = $existingMatches->keys()->diff($incomingIds)->isNotEmpty();

            ClubMatch::query()
                ->where('team_id', $manageableTeam->id)
                ->where('season', $season)
                ->when(
                    count($incomingIds) > 0,
                    fn ($query) => $query->whereNotIn('id', $incomingIds),
                    fn ($query) => $query,
                )
                ->delete();

            foreach ($rows as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $matchId = $row['id'] ?? null;
                $payload = [
                    'date' => $row['date'],
                    'versus' => $row['versus'],
                    'at_home' => (bool) $row['at_home'],
                    'city' => $row['city'] ?: null,
                ];

                if ($matchId !== null) {
                    $match = $existingMatches->get((int) $matchId);

                    if ($match === null) {
                        throw ValidationException::withMessages([
                            "rows.$index.id" => 'Diese Spiel-ID gehört nicht zu diesem Team und dieser Saison.',
                        ]);
                    }

                    $match->fill($payload);

                    if ($match->isDirty()) {
                        $match->save();
                        $hasChanges = true;
                    }

                    continue;
                }

                ClubMatch::query()->create([
                    ...$payload,
                    'tenant_id' => $manageableTeam->tenant_id,
                    'team_id' => $manageableTeam->id,
                    'season' => $season,
                ]);
                $hasChanges = true;
            }

            if ($hasChanges) {
                $this->scheduleUpdateNotifier->notifyTeamPlayers(
                    $manageableTeam,
                    'Spielplan',
                    route('spielplan', ['season' => $season]),
                );
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Spielplan gespeichert.']);

        return to_route('spielplan', ['season' => $season]);
    }

    /**
     * @param  array<int, int>  $teamIds
     * @param  array<int, string>  $seasons
     */
    private function defaultSeason(?int $tenantId, array $teamIds, array $seasons): ?string
    {
        if ($tenantId === null || count($teamIds) === 0) {
            return null;
        }

        $upcoming = ClubMatch::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('team_id', $teamIds)
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->first();

        if ($upcoming !== null) {
            return $upcoming->season;
        }

        return $seasons[0] ?? null;
    }
}
