<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Services\Teams\TeamPermissionSynchronizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;

class ConfirmTeamPayment
{
    public function __construct(private TeamPermissionSynchronizer $permissionSynchronizer) {}

    public function handle(Team $team, User $payer, string $sessionId): void
    {
        DB::transaction(function () use ($team, $payer, $sessionId): void {
            $lockedTeam = Team::query()
                ->whereKey($team->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTeam->payment_status === 'paid') {
                if ($lockedTeam->payment_reference !== $sessionId) {
                    Log::warning('Ignoring a second completed Stripe session for an already paid team.', [
                        'team_id' => $lockedTeam->id,
                        'stripe_session_id' => $sessionId,
                    ]);

                    return;
                }

                $payer->switchTeam($lockedTeam);

                return;
            }

            if (! in_array($lockedTeam->payment_status, ['pending', 'skipped'], true)) {
                throw new LogicException('The team is not eligible for payment confirmation.');
            }

            $lockedTeam->update([
                'payment_status' => 'paid',
                'payment_reference' => $sessionId,
                'payment_paid_at' => now(),
            ]);

            $membership = $lockedTeam->memberships()->updateOrCreate(
                ['user_id' => $payer->id],
                ['role' => TeamRole::Owner],
            );

            $this->permissionSynchronizer->synchronizeMembership($membership);

            $payer->switchTeam($lockedTeam);
        });
    }
}
