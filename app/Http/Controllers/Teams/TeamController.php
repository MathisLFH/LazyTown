<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateTeam;
use App\Actions\Teams\DeleteTeam;
use App\Enums\InvitationRole;
use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\DeleteTeamRequest;
use App\Http\Requests\Teams\SaveTeamRequest;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class TeamController extends Controller
{
    public function editCurrent(Request $request): Response
    {
        return $this->edit($request, $request->user()->currentTeam()->firstOrFail());
    }

    public function meinTeam(Request $request): Response
    {
        $user = $request->user();
        $activeClubTeams = $user->teams()
            ->where('teams.is_personal', false)
            ->wherePivot('status', 'active');
        $team = (clone $activeClubTeams)
            ->whereKey($user->current_team_id)
            ->first()
            ?? $activeClubTeams->firstOrFail();

        return Inertia::render('MeinTeam', $this->memberManagementProps(
            $user,
            $team,
        ));
    }

    /**
     * Display a listing of the user's teams.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('teams/Index', [
            'teams' => $user->toUserTeams(includeCurrent: true)
                ->where('isPersonal', false)
                ->values(),
        ]);
    }

    /**
     * Store a newly created team.
     */
    public function store(SaveTeamRequest $request, CreateTeam $createTeam): RedirectResponse
    {
        $team = $createTeam->handle($request->user(), $request->validated('name'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team created.')]);

        return $request->user()->hasRole('trainer')
            ? to_route('teams.payment.edit', ['team' => $team->slug])
            : to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Show the team edit page.
     */
    public function edit(Request $request, Team $team): Response
    {
        $user = $request->user();
        $trainer = $team->assignedTrainer()->first(['users.id', 'users.name']);

        return Inertia::render('teams/Edit', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'isPersonal' => $team->is_personal,
                'paymentStatus' => $team->payment_status,
                'trainerName' => $trainer?->name,
            ],
            'permissions' => $user->toTeamPermissions($team),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function memberManagementProps(User $user, Team $team): array
    {
        $permissions = $user->toTeamPermissions($team);

        return [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'logoUrl' => $permissions->canUpdateTeam && $team->logo_path
                    ? Storage::disk('public')->url($team->logo_path)
                    : null,
                'isPersonal' => $team->is_personal,
                'paymentStatus' => $team->payment_status,
            ],
            'members' => $team->members()->wherePivot('status', 'active')->get()->map(function (User $member) {
                /** @var Membership $membership */
                $membership = $member->getRelation('pivot');

                return [
                    'id' => $member->id,
                    'public_id' => $member->public_id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'phone' => $member->phone,
                    'avatar' => $member->avatar ?? null,
                    'role' => $membership->role->value,
                    'role_label' => $membership->role->label(),
                ];
            }),
            'invitations' => $team->invitations()
                ->whereNull('accepted_at')
                ->get()
                ->map(fn ($invitation) => [
                    'code' => $invitation->code,
                    'email' => $invitation->email,
                    'role' => $invitation->role->value,
                    'role_label' => $invitation->role === TeamRole::Admin
                        ? InvitationRole::Trainer->label()
                        : InvitationRole::Player->label(),
                    'created_at' => $invitation->created_at->toISOString(),
                ]),
            'permissions' => $permissions,
            'availableRoles' => TeamRole::assignable(),
            'availableInvitationRoles' => InvitationRole::options(),
        ];
    }

    public function updateLogo(Request $request, Team $team): RedirectResponse
    {
        abort_unless(
            ! $team->is_personal && $request->user()->belongsToTeam($team),
            404,
        );

        Gate::authorize('update', $team);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'logo.required' => 'Bitte wähle ein Vereinslogo aus.',
            'logo.image' => 'Das Vereinslogo muss eine Bilddatei sein.',
            'logo.mimes' => 'Das Vereinslogo muss ein JPG-, PNG- oder WebP-Bild sein.',
            'logo.max' => 'Das Vereinslogo darf höchstens 2 MB groß sein.',
        ]);

        $logo = $request->file('logo');

        if (! $logo instanceof UploadedFile) {
            throw new RuntimeException('Validated team logo upload was not available.');
        }

        $logoPath = $logo->storePublicly('team-logos/'.$team->id, 'public');

        if ($logoPath === false) {
            throw new RuntimeException('The team logo could not be stored.');
        }

        $previousLogoPath = $team->logo_path;
        $team->update(['logo_path' => $logoPath]);

        if ($previousLogoPath !== null && ! Storage::disk('public')->delete($previousLogoPath)) {
            logger()->warning('Unable to delete the previous team logo.', ['team_id' => $team->id]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Vereinslogo wurde aktualisiert.']);

        return to_route('mein-team');
    }

    /**
     * Update the specified team.
     */
    public function update(SaveTeamRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $team = DB::transaction(function () use ($request, $team) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();

            $team->update(['name' => $request->validated('name')]);

            return $team;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team updated.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Switch the user's current team.
     */
    public function switch(Request $request, Team $team): RedirectResponse
    {
        abort_unless($request->user()->belongsToTeam($team), 403);

        $request->user()->switchTeam($team);

        return back();
    }

    /**
     * Leave the specified team.
     */
    public function leave(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('leave', $team);

        $user = $request->user();

        $fallbackTeam = $user->isCurrentTeam($team)
            ? $user->fallbackTeam($team)
            : null;

        $team->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($fallbackTeam) {
            $user->switchTeam($fallbackTeam);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You left the team ":name"', ['name' => $team->name])]);

        return to_route('teams.index');
    }

    /**
     * Delete the specified team.
     */
    public function destroy(DeleteTeamRequest $request, Team $team, DeleteTeam $deleteTeam): RedirectResponse
    {
        $deleteTeam->handle($request->user(), $team);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team deleted.')]);

        return $request->routeIs('admin.teams.destroy')
            ? to_route('admin.teams.index')
            : to_route('teams.index');
    }
}
