<?php

namespace App\Http\Controllers\Teams;

use App\Enums\InvitationRole;
use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\AddTeamMemberRequest;
use App\Http\Requests\Teams\UpdateTeamMemberRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamMemberController extends Controller
{
    public function store(AddTeamMemberRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('addMember', $team);

        $member = User::where('email', $request->validated('email'))->firstOrFail();
        $teamRole = InvitationRole::from($request->validated('role'))->teamRole();

        $team->memberships()->updateOrCreate(
            ['user_id' => $member->id],
            ['role' => $teamRole, 'status' => 'pending'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mitglied wurde zur Bestätigung zugeordnet.']);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    public function confirm(Request $request, Team $team): RedirectResponse
    {
        $membership = $team->memberships()
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $membership->update(['status' => 'active']);
        $request->user()->switchTeam($team);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Vereinsbeitritt bestätigt.']);

        return to_route('home');
    }

    /**
     * Update the specified team member's role.
     */
    public function update(UpdateTeamMemberRequest $request, Team $team, User $user): RedirectResponse
    {
        Gate::authorize('updateMember', $team);

        $newRole = TeamRole::from($request->validated('role'));

        $team->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail()
            ->update(['role' => $newRole]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member role updated.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Remove the specified team member.
     */
    public function destroy(Team $team, User $user): RedirectResponse
    {
        Gate::authorize('removeMember', $team);

        abort_if($team->owner()?->is($user), 403, __('The team owner cannot be removed.'));

        $team->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->delete();

        if ($user->isCurrentTeam($team)) {
            $user->switchTeam($user->personalTeam());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }
}
