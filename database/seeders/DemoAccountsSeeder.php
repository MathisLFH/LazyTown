<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Teams\TeamPermissionSynchronizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoAccountsSeeder extends Seeder
{
    private const PASSWORD = '12345678';

    public function run(TeamPermissionSynchronizer $permissionSynchronizer): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Demo accounts can only be seeded in local or testing environments.');
        }

        DB::transaction(function () use ($permissionSynchronizer): void {
            $premiumAdmin = $this->updateDemoUser(
                email: 'premium@example.com',
                name: 'Premium Testverein',
                roles: [UserRole::Administration->value],
                activeRole: UserRole::Administration->value,
            );
            $freeAdmin = $this->updateDemoUser(
                email: 'free@example.com',
                name: 'Kostenloser Testverein',
                roles: [UserRole::Administration->value],
                activeRole: UserRole::Administration->value,
            );

            $premiumTenant = Tenant::query()->updateOrCreate(
                ['subdomain' => 'demo-premium'],
                [
                    'admin_id' => $premiumAdmin->id,
                    'name' => 'Demo Premium Verein',
                ],
            );
            $freeTenant = Tenant::query()->updateOrCreate(
                ['subdomain' => 'demo-free'],
                [
                    'admin_id' => $freeAdmin->id,
                    'name' => 'Demo kostenloser Verein',
                ],
            );

            $premiumAdmin->update(['tenant_id' => $premiumTenant->id]);
            $freeAdmin->update(['tenant_id' => $freeTenant->id]);

            $premiumSubscription = Subscription::query()->firstOrCreate(
                ['type' => 'premium'],
                ['price' => 29.99],
            );
            $freeSubscription = Subscription::query()->firstOrCreate(
                ['type' => 'free'],
                ['price' => 0],
            );

            $premiumTenant->subscriptions()->sync([
                $premiumSubscription->id => [
                    'subscription_time' => now(),
                    'payment_type' => null,
                ],
            ]);
            $freeTenant->subscriptions()->sync([
                $freeSubscription->id => [
                    'subscription_time' => now(),
                    'payment_type' => null,
                ],
            ]);

            $premiumTeam = $this->updateDemoTeam(
                tenant: $premiumTenant,
                owner: $premiumAdmin,
                slug: 'demo-premium-verein',
                name: 'Demo Premium Verein',
                paymentStatus: 'paid',
                paymentReference: 'PAY-DEMO',
            );
            $freeTeam = $this->updateDemoTeam(
                tenant: $freeTenant,
                owner: $freeAdmin,
                slug: 'demo-kostenloser-verein',
                name: 'Demo kostenloser Verein',
                paymentStatus: 'not_required',
            );

            $premiumAdmin->update(['current_team_id' => $premiumTeam->id]);
            $freeAdmin->update(['current_team_id' => $freeTeam->id]);

            $trainer = $this->updateDemoUser(
                email: 'trainer@example.com',
                name: 'Demo Trainer',
                roles: [UserRole::Coach->value],
                activeRole: UserRole::Coach->value,
                tenantId: $premiumTenant->id,
                currentTeamId: $premiumTeam->id,
            );
            $player = $this->updateDemoUser(
                email: 'player@example.com',
                name: 'Demo Spieler',
                roles: [UserRole::Player->value],
                activeRole: UserRole::Player->value,
                tenantId: $premiumTenant->id,
                currentTeamId: $premiumTeam->id,
            );

            $this->synchronizeMembership($premiumTeam, $premiumAdmin, TeamRole::Owner, $permissionSynchronizer);
            $this->synchronizeMembership($premiumTeam, $trainer, TeamRole::Admin, $permissionSynchronizer);
            $this->synchronizeMembership($premiumTeam, $player, TeamRole::Member, $permissionSynchronizer);
            $this->synchronizeMembership($freeTeam, $freeAdmin, TeamRole::Owner, $permissionSynchronizer);
        });
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function updateDemoUser(
        string $email,
        string $name,
        array $roles,
        string $activeRole,
        ?int $tenantId = null,
        ?int $currentTeamId = null,
    ): User {
        $parts = explode(' ', $name, 2);

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'first_name' => $parts[0],
                'last_name' => $parts[1] ?? '',
                'password' => self::PASSWORD,
                'roles' => $roles,
                'active_role' => $activeRole,
                'tenant_id' => $tenantId,
                'current_team_id' => $currentTeamId,
            ],
        );

        $user->forceFill(['must_change_password' => false])->save();

        return $user;
    }

    private function updateDemoTeam(
        Tenant $tenant,
        User $owner,
        string $slug,
        string $name,
        string $paymentStatus,
        ?string $paymentReference = null,
    ): Team {
        return Team::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'tenant_id' => $tenant->id,
                'name' => $name,
                'is_personal' => false,
                'created_by' => $owner->id,
                'payment_status' => $paymentStatus,
                'payment_reference' => $paymentReference,
                'payment_paid_at' => $paymentStatus === 'paid' ? now() : null,
            ],
        );
    }

    private function synchronizeMembership(
        Team $team,
        User $user,
        TeamRole $role,
        TeamPermissionSynchronizer $permissionSynchronizer,
    ): void {
        $membership = $team->memberships()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'tenant_id' => $team->tenant_id,
                'role' => $role,
                'status' => 'active',
            ],
        );

        $permissionSynchronizer->synchronizeMembership($membership);
    }
}
