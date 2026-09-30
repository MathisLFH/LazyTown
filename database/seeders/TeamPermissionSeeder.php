<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Services\Teams\TeamPermissionSynchronizer;
use Illuminate\Database\Seeder;

class TeamPermissionSeeder extends Seeder
{
    public function run(TeamPermissionSynchronizer $permissionSynchronizer): void
    {
        Team::query()
            ->with('memberships')
            ->each(function (Team $team) use ($permissionSynchronizer): void {
                $permissionSynchronizer->synchronizeTeam($team);
            });
    }
}
