<?php

namespace App\Http\Controllers\Teams;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateTenantUserRolesRequest;
use App\Http\Requests\Teams\CreateTenantMemberRequest;
use App\Http\Requests\Teams\DeleteTenantMemberRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberAdministrationController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $this->tenantFor($request);
        $currentUser = $request->user();

        return Inertia::render('admin/members/Index', [
            'members' => $tenant->users()
                ->orderBy('name')
                ->orderBy('id')
                ->select(['id', 'name', 'email', 'roles', 'must_change_password'])
                ->withExists([
                    'ownedTeams as has_non_personal_owned_teams' => fn ($query) => $query
                        ->where('teams.is_personal', false),
                ])
                ->get()
                ->map(fn (User $member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'roles' => $member->roles,
                    'must_change_password' => $member->must_change_password,
                    'canManageRoles' => ! $member->is($currentUser),
                    'canDelete' => ! $member->is($currentUser)
                        && $tenant->admin_id !== $member->id
                        && ! $member->has_non_personal_owned_teams,
                    'deleteBlockedReason' => $member->has_non_personal_owned_teams
                        ? 'Teamverantwortliche müssen vor dem Löschen erst die Verantwortung übertragen.'
                        : ($tenant->admin_id === $member->id
                            ? 'Der Vereinsadministrator kann nicht gelöscht werden.'
                            : null),
                ]),
        ]);
    }

    public function updateRoles(UpdateTenantUserRolesRequest $request, User $user): RedirectResponse
    {
        $tenant = $this->tenantFor($request);
        abort_unless($user->tenant_id === $tenant->id, 404);
        abort_if($user->is($request->user()), 403);

        $managedRoles = $request->validated('roles', []);
        $roles = array_values(array_unique([
            ...array_filter(
                $user->roles ?: [UserRole::Player->value],
                fn (string $role): bool => ! in_array($role, [
                    UserRole::Coach->value,
                    UserRole::Administration->value,
                ], true),
            ),
            ...$managedRoles,
        ]));

        if ($roles === []) {
            $roles = [UserRole::Player->value];
        }

        $activeRole = $user->active_role;

        if (! in_array($activeRole, $roles, true)) {
            $activeRole = in_array(UserRole::Player->value, $roles, true)
                ? UserRole::Player->value
                : $roles[0];
        }

        $user->update([
            'roles' => $roles,
            'active_role' => $activeRole,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rollen aktualisiert.']);

        return to_route('admin.members.index');
    }

    public function store(CreateTenantMemberRequest $request): RedirectResponse
    {
        $member = new User([
            'name' => 'Mitglied',
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'roles' => [UserRole::Player->value],
            'active_role' => UserRole::Player->value,
        ]);
        $member->must_change_password = true;

        $this->tenantFor($request)->users()->save($member);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mitglied angelegt.']);

        return to_route('admin.members.index');
    }

    public function destroy(DeleteTenantMemberRequest $request, User $user): RedirectResponse
    {
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mitglied gelöscht.']);

        return to_route('admin.members.index');
    }

    private function tenantFor(Request $request): Tenant
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->tenant instanceof Tenant, 403);

        return $user->tenant;
    }
}
