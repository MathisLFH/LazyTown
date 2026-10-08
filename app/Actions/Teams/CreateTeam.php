<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Services\Teams\TeamPermissionSynchronizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTeam
{
    public function __construct(private TeamPermissionSynchronizer $permissionSynchronizer) {}

    /**
     * Create a new team and optionally add the user as owner.
     */
    public function handle(
        User $user,
        string $name,
        bool $isPersonal = false,
        bool $addOwner = true,
        bool $allowMultiple = false,
        bool $switchToTeam = true,
    ): Team {
        return DB::transaction(function () use ($user, $name, $isPersonal, $addOwner, $allowMultiple, $switchToTeam) {
            if (! $isPersonal && ! $allowMultiple && $user->teams()->where('is_personal', false)->exists()) {
                throw ValidationException::withMessages([
                    'name' => 'Du kannst nur einen Verein haben.',
                ]);
            }

            $team = Team::create([
                'tenant_id' => $user->tenant_id,
                'name' => $name,
                'is_personal' => $isPersonal,
                'created_by' => $user->id,
            ]);

            if ($addOwner) {
                $membership = $team->memberships()->create([
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'role' => TeamRole::Owner,
                ]);

                $this->permissionSynchronizer->synchronizeMembership($membership);

                if ($switchToTeam) {
                    $user->switchTeam($team);
                }
            }

            return $team;
        });
    }
}
