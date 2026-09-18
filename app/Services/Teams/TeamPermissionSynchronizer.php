<?php

namespace App\Services\Teams;

use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TeamPermissionSynchronizer
{
    /**
     * Synchronize the package roles and permissions for a team.
     */
    public function synchronizeTeam(Team $team): void
    {
        DB::transaction(function () use ($team): void {
            setPermissionsTeamId($team->id);
            $this->ensurePermissions();
            $guardName = config('auth.defaults.guard', 'web');

            foreach (TeamRole::cases() as $teamRole) {
                $role = Role::findOrCreate($teamRole->value, $guardName);
                $role->syncPermissions($teamRole->permissions());
            }

            $team->memberships()->each(function (Membership $membership): void {
                $this->synchronizeMembership($membership);
            });
        });
    }

    /**
     * Synchronize one membership against the package role for its team.
     */
    public function synchronizeMembership(Membership $membership): void
    {
        setPermissionsTeamId($membership->team_id);
        $this->ensurePermissions();
        $guardName = config('auth.defaults.guard', 'web');

        $role = Role::findOrCreate($membership->role->value, $guardName);
        $role->syncPermissions($membership->role->permissions());
        $membership->syncRoles([$role]);
    }

    private function ensurePermissions(): void
    {
        $guardName = config('auth.defaults.guard', 'web');

        foreach (TeamPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, $guardName);
        }
    }
}
