<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plans\ParseCsvRequest;
use App\Http\Requests\Plans\SaveTrainingPlanRequest;
use App\Models\Gym;
use App\Models\Team;
use App\Models\Training;
use App\Services\Plans\PlanCsvParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TrainingPlanController extends Controller
{
    public function __construct(private readonly PlanCsvParser $csvParser) {}

    public function show(Request $request): Response
    {
        $user = $request->user();
        $visibleTeams = $user->clubTeamsForPlans();
        $manageableTeamIds = $user->manageableTeamsForPlans()->pluck('id')->all();
        $tenantId = $visibleTeams->first()?->tenant_id;

        $gyms = $tenantId === null
            ? collect()
            : Gym::query()
                ->where(function (Builder $query) use ($tenantId): void {
                    $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
                })
                ->orderByRaw('LOWER(name)')
                ->get()
                ->map(fn (Gym $gym) => [
                    'id' => $gym->id,
                    'name' => $gym->name,
                    'shared' => $gym->tenant_id === null,
                ]);

        $trainings = $tenantId === null
            ? collect()
            : Training::query()
                ->select([
                    'trainings.id',
                    'trainings.team_id',
                    'trainings.gym_id',
                    'trainings.weekday',
                    'trainings.starts_at',
                    'trainings.ends_at',
                    'trainings.tenant_id',
                    'teams.name as team_name',
                    'gyms.name as gym_name',
                ])
                ->join('teams', 'teams.id', '=', 'trainings.team_id')
                ->leftJoin('gyms', 'gyms.id', '=', 'trainings.gym_id')
                ->where('trainings.tenant_id', $tenantId)
                ->whereIn('trainings.team_id', $visibleTeams->pluck('id'))
                ->orderByRaw($this->weekdayOrderSql())
                ->orderBy('trainings.starts_at')
                ->orderBy('trainings.id')
                ->get()
                ->map(fn ($training) => [
                    'id' => $training->id,
                    'team_id' => $training->team_id,
                    'team_name' => $training->team_name,
                    'gym_id' => $training->gym_id,
                    'gym_name' => $training->gym_name,
                    'weekday' => $training->weekday,
                    'starts_at' => substr((string) $training->starts_at, 0, 5),
                    'ends_at' => substr((string) $training->ends_at, 0, 5),
                ]);

        return Inertia::render('Hallenplan', [
            'teams' => $visibleTeams->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'canEdit' => in_array($team->id, $manageableTeamIds, true),
            ])->values(),
            'gyms' => $gyms->values(),
            'trainings' => $trainings->values(),
            'canEdit' => count($manageableTeamIds) > 0,
        ]);
    }

    public function parseCsv(ParseCsvRequest $request): JsonResponse
    {
        $rows = $this->csvParser->readRows($request->file('file'));
        $manageableTeams = $request->user()->manageableTeamsForPlans();
        $teamByNormalizedName = $manageableTeams->keyBy(
            fn (Team $team): string => $this->csvParser->normalizeComparableName($team->name),
        );

        $errors = [];
        $parsedRows = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_line'] ?? 0);
            $weekday = $this->csvParser->parseWeekday($row['wochentag'] ?? null);
            $startsAt = $this->csvParser->parseTime($row['beginn'] ?? null);
            $endsAt = $this->csvParser->parseTime($row['ende'] ?? null);
            $gymName = trim((string) ($row['halle'] ?? ''));
            $teamName = trim((string) ($row['mannschaft'] ?? ''));

            if ($weekday === null || $startsAt === null || $endsAt === null || $gymName === '') {
                $errors[] = [
                    'line' => $line,
                    'message' => 'Pflichtfelder fehlen oder sind ungültig.',
                ];

                continue;
            }

            if ($startsAt >= $endsAt) {
                $errors[] = [
                    'line' => $line,
                    'message' => 'Die Endzeit muss nach der Startzeit liegen.',
                ];

                continue;
            }

            $team = null;

            if ($teamName !== '') {
                $team = $teamByNormalizedName->get($this->csvParser->normalizeComparableName($teamName));

                if ($team === null) {
                    $errors[] = [
                        'line' => $line,
                        'message' => 'Unbekannte Mannschaft.',
                    ];

                    continue;
                }
            } elseif ($manageableTeams->count() === 1) {
                $team = $manageableTeams->first();
                $teamName = $team?->name ?? '';
            } else {
                $errors[] = [
                    'line' => $line,
                    'message' => 'Mannschaft fehlt.',
                ];

                continue;
            }

            $parsedRows[] = [
                'weekday' => $weekday,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'gym_name' => $gymName,
                'team_id' => $team?->id,
                'team_name' => $teamName,
            ];
        }

        return response()->json([
            'rows' => $parsedRows,
            'errors' => $errors,
        ]);
    }

    public function sync(SaveTrainingPlanRequest $request): RedirectResponse
    {
        $user = $request->user();
        $manageableTeams = $user->manageableTeamsForPlans();
        $tenantId = $manageableTeams->first()?->tenant_id;

        if ($tenantId === null) {
            abort(403);
        }

        DB::transaction(function () use ($request, $tenantId, $manageableTeams): void {
            $rows = $request->validated('rows');

            if (! is_array($rows)) {
                $rows = [];
            }

            $manageableTeamIds = $manageableTeams->pluck('id')->all();

            $existingTrainings = Training::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('team_id', $manageableTeamIds)
                ->get()
                ->keyBy('id');

            $incomingIds = collect($rows)
                ->pluck('id')
                ->filter(fn ($id) => $id !== null)
                ->map(fn ($id) => (int) $id)
                ->all();

            Training::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('team_id', $manageableTeamIds)
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

                $trainingId = $row['id'] ?? null;
                $gym = $this->resolveGym($tenantId, (string) $row['gym_name']);
                $payload = [
                    'team_id' => (int) $row['team_id'],
                    'gym_id' => $gym->id,
                    'weekday' => $row['weekday'],
                    'starts_at' => $row['starts_at'],
                    'ends_at' => $row['ends_at'],
                ];

                if ($trainingId !== null) {
                    $training = $existingTrainings->get((int) $trainingId);

                    if ($training === null) {
                        throw ValidationException::withMessages([
                            "rows.$index.id" => 'Diese Trainings-ID gehört nicht zu einem bearbeitbaren Team.',
                        ]);
                    }

                    $training->update($payload);

                    continue;
                }

                Training::query()->create([
                    ...$payload,
                    'tenant_id' => $tenantId,
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hallenplan gespeichert.']);

        return to_route('hallenplan');
    }

    private function resolveGym(int $tenantId, string $name): Gym
    {
        $trimmedName = trim($name);

        $existingGym = Gym::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($trimmedName)])
            ->where(function (Builder $query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderByRaw('tenant_id IS NULL')
            ->first();

        if ($existingGym !== null) {
            return $existingGym;
        }

        return Gym::query()->create([
            'tenant_id' => $tenantId,
            'name' => $trimmedName,
        ]);
    }

    private function weekdayOrderSql(): string
    {
        return "CASE trainings.weekday
            WHEN 'Montag' THEN 1
            WHEN 'Dienstag' THEN 2
            WHEN 'Mittwoch' THEN 3
            WHEN 'Donnerstag' THEN 4
            WHEN 'Freitag' THEN 5
            WHEN 'Samstag' THEN 6
            WHEN 'Sonntag' THEN 7
            ELSE 99
        END";
    }
}
