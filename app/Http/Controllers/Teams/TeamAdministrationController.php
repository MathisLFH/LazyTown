<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamRole;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\AssignTeamTrainerRequest;
use App\Http\Requests\Teams\SaveTeamRequest;
use App\Models\Membership;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Teams\TeamPermissionSynchronizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TeamAdministrationController extends Controller
{
    public function __construct(private TeamPermissionSynchronizer $permissionSynchronizer) {}

    public function index(Request $request): Response
    {
        $tenant = $this->tenantFor($request);
        $teams = $tenant->teams()
            ->where('is_personal', false)
            ->with('members')
            ->orderBy('name')
            ->get()
            ->map(function (Team $team): array {
                $trainer = $team->members->first(function (User $member): bool {
                    /** @var Membership $membership */
                    $membership = $member->getRelation('pivot');

                    return $membership->role === TeamRole::Admin
                        && $member->hasRole(UserRole::Coach->value);
                });

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'slug' => $team->slug,
                    'isPersonal' => $team->is_personal,
                    'trainerId' => $trainer?->id,
                    'trainerName' => $trainer?->name,
                ];
            });
        $trainers = User::query()
            ->whereJsonContains('roles', UserRole::Coach->value)
            ->where(fn ($query) => $query
                ->whereNull('tenant_id')
                ->orWhere('tenant_id', $tenant->id))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('admin/teams/Index', [
            'teams' => $teams,
            'trainers' => $trainers,
        ]);
    }

    public function store(SaveTeamRequest $request, CreateTeam $createTeam): RedirectResponse
    {
        $tenant = $this->tenantFor($request);
        $team = $createTeam->handle(
            $request->user(),
            $request->validated('name'),
            allowMultiple: true,
            switchToTeam: false,
        );
        $team->update(['payment_status' => 'not_required']);

        abort_unless($team->tenant_id === $tenant->id, 404);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Team erstellt.']);

        return to_route('admin.teams.index');
    }

    public function updateTrainer(AssignTeamTrainerRequest $request, Team $team): RedirectResponse
    {
        $tenant = $this->tenantFor($request);
        abort_unless($team->tenant_id === $tenant->id, 404);

        $trainer = $request->validated('trainer_id')
            ? User::query()
                ->whereKey($request->validated('trainer_id'))
                ->whereJsonContains('roles', UserRole::Coach->value)
                ->where(fn ($query) => $query
                    ->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id))
                ->firstOrFail()
            : null;

        if ($trainer && $team->owner()?->is($trainer)) {
            throw ValidationException::withMessages([
                'trainer_id' => 'Der Teamverantwortliche kann nicht als Trainer zugewiesen werden.',
            ]);
        }

        DB::transaction(function () use ($team, $tenant, $trainer): void {
            $previousTrainerMemberships = $team->memberships()
                ->where('role', TeamRole::Admin)
                ->whereHas('user', fn ($query) => $query->whereJsonContains('roles', UserRole::Coach->value))
                ->get();

            $previousTrainerMemberships->each(function (Membership $membership) use ($trainer): void {
                if ($trainer?->is($membership->user)) {
                    return;
                }

                $membership->update(['role' => TeamRole::Member]);
                $this->permissionSynchronizer->synchronizeMembership($membership->fresh());
            });

            if (! $trainer) {
                return;
            }

            $trainer->update(['tenant_id' => $tenant->id]);
            $membership = $team->memberships()->updateOrCreate(
                ['user_id' => $trainer->id],
                [
                    'tenant_id' => $tenant->id,
                    'role' => TeamRole::Admin,
                    'status' => 'active',
                ],
            );

            $this->permissionSynchronizer->synchronizeMembership($membership);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Trainer zugewiesen.']);

        return to_route('admin.teams.index');
    }

    private function tenantFor(Request $request): Tenant
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->tenant instanceof Tenant, 403);

        return $user->tenant;
    }
}
