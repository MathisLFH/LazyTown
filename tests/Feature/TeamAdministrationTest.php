<?php

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('administrators see teams and trainers from their tenant only', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $tenant->update(['admin_id' => $administrator->id]);
    $team = Team::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Senioren']);
    $team->members()->attach($administrator, [
        'tenant_id' => $tenant->id,
        'role' => TeamRole::Owner->value,
    ]);
    $trainer = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['trainer'],
    ]);
    $otherTenant = Tenant::factory()->create();
    $otherTrainer = User::factory()->create([
        'tenant_id' => $otherTenant->id,
        'roles' => ['trainer'],
    ]);

    $this->actingAs($administrator)
        ->get(route('admin.teams.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/teams/Index')
            ->has('teams', 1)
            ->where('teams.0.name', 'Senioren')
            ->has('trainers', 1)
            ->where('trainers.0.id', $trainer->id)
            ->where('trainers.0.id', fn ($id) => $id !== $otherTrainer->id)
            ->has('users', 1)
            ->where('users.0.id', $trainer->id),
        );
});

test('administrators can create additional teams in their tenant', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $tenant->update(['admin_id' => $administrator->id]);
    $existingTeam = Team::factory()->create(['tenant_id' => $tenant->id]);
    $existingTeam->members()->attach($administrator, [
        'tenant_id' => $tenant->id,
        'role' => TeamRole::Owner->value,
    ]);
    $originalCurrentTeamId = $administrator->current_team_id;

    $this->actingAs($administrator)
        ->post(route('admin.teams.store'), ['name' => 'Junioren'])
        ->assertRedirect(route('admin.teams.index'));

    $newTeam = Team::where('name', 'Junioren')->firstOrFail();

    expect($newTeam->tenant_id)->toBe($tenant->id)
        ->and($newTeam->payment_status)->toBe('not_required')
        ->and($newTeam->memberships()->where('user_id', $administrator->id)->firstOrFail()->role)->toBe(TeamRole::Owner)
        ->and($newTeam->memberships()->where('user_id', $administrator->id)->value('tenant_id'))->toBe($tenant->id)
        ->and($administrator->fresh()->current_team_id)->toBe($originalCurrentTeamId);
});

test('administrators can assign and replace a trainer on a tenant team', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $tenant->update(['admin_id' => $administrator->id]);
    $team = Team::factory()->create(['tenant_id' => $tenant->id]);
    $team->members()->attach($administrator, [
        'tenant_id' => $tenant->id,
        'role' => TeamRole::Owner->value,
    ]);
    $previousTrainer = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['trainer'],
    ]);
    $previousMembership = $team->memberships()->create([
        'user_id' => $previousTrainer->id,
        'tenant_id' => $tenant->id,
        'role' => TeamRole::Admin,
        'status' => 'active',
    ]);
    $newTrainer = User::factory()->create(['roles' => ['trainer']]);
    $newTrainer->update(['tenant_id' => $tenant->id]);

    $this->actingAs($administrator)
        ->patch(route('admin.teams.trainer.update', $team), [
            'trainer_id' => $newTrainer->id,
        ])
        ->assertRedirect(route('admin.teams.index'));

    expect($newTrainer->fresh()->tenant_id)->toBe($tenant->id)
        ->and($team->memberships()->where('user_id', $newTrainer->id)->firstOrFail()->role)->toBe(TeamRole::Admin)
        ->and($team->memberships()->where('user_id', $newTrainer->id)->value('status'))->toBe('active')
        ->and($previousMembership->fresh()->role)->toBe(TeamRole::Member)
        ->and($previousTrainer->fresh()->belongsToTeam($team))->toBeTrue();
});

test('team trainer assignment rejects non trainers and cross tenant teams', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $tenant->update(['admin_id' => $administrator->id]);
    $team = Team::factory()->create(['tenant_id' => $tenant->id]);
    $team->members()->attach($administrator, [
        'tenant_id' => $tenant->id,
        'role' => TeamRole::Owner->value,
    ]);
    $player = User::factory()->create(['tenant_id' => $tenant->id, 'roles' => ['spieler']]);
    $foreignTeam = Team::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);

    $this->actingAs($administrator)
        ->patch(route('admin.teams.trainer.update', $team), ['trainer_id' => $player->id])
        ->assertSessionHasErrors('trainer_id');

    $this->patch(route('admin.teams.trainer.update', $foreignTeam), ['trainer_id' => null])
        ->assertNotFound();
});

test('only administrators can open team administration', function () {
    $user = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);

    $this->actingAs($user)
        ->get(route('admin.teams.index'))
        ->assertForbidden();
});

test('administrators can delete teams in their tenant after confirming the team name', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $tenant->update(['admin_id' => $administrator->id]);
    $team = app(CreateTeam::class)->handle(
        $administrator,
        'Junioren',
        allowMultiple: true,
        switchToTeam: false,
    );
    $teamId = $team->id;

    $this->actingAs($administrator)
        ->delete(route('admin.teams.destroy', $team), ['name' => 'Junioren'])
        ->assertRedirect(route('admin.teams.index'));

    expect(Team::withTrashed()->findOrFail($teamId)->trashed())->toBeTrue();
});

test('administrators cannot delete teams from another tenant', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $tenant->update(['admin_id' => $administrator->id]);
    $otherTenant = Tenant::factory()->create();
    $otherAdministrator = User::factory()->create(['tenant_id' => $otherTenant->id]);
    $foreignTeam = app(CreateTeam::class)->handle(
        $otherAdministrator,
        'Fremdes Team',
        allowMultiple: true,
        switchToTeam: false,
    );

    $this->actingAs($administrator)
        ->delete(route('admin.teams.destroy', $foreignTeam), ['name' => 'Fremdes Team'])
        ->assertNotFound();

    expect(Team::find($foreignTeam->id))->not->toBeNull();
});

test('administrators can grant and revoke trainer and administration roles within their tenant', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $tenant->update(['admin_id' => $administrator->id]);
    $member = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);

    $this->actingAs($administrator)
        ->patch(route('admin.users.roles.update', $member), [
            'roles' => ['trainer', 'verwaltung'],
        ])
        ->assertRedirect(route('admin.teams.index'));

    expect($member->fresh()->roles)->toBe(['spieler', 'trainer', 'verwaltung']);

    $this->patch(route('admin.users.roles.update', $member), [
        'roles' => [],
    ])->assertRedirect(route('admin.teams.index'));

    expect($member->fresh()->roles)->toBe(['spieler'])
        ->and($member->fresh()->active_role)->toBe('spieler');
});

test('administrators cannot grant roles across tenants or change their own roles', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $tenant->update(['admin_id' => $administrator->id]);
    $foreignTenantUser = User::factory()->create([
        'tenant_id' => Tenant::factory()->create()->id,
        'roles' => ['spieler'],
    ]);

    $this->actingAs($administrator)
        ->patch(route('admin.users.roles.update', $foreignTenantUser), [
            'roles' => ['verwaltung'],
        ])
        ->assertNotFound();

    $this->patch(route('admin.users.roles.update', $administrator), [
        'roles' => ['trainer'],
    ])->assertForbidden();

    expect($administrator->fresh()->roles)->toBe(['verwaltung']);
});

test('non-administrators cannot grant application roles', function () {
    $tenant = Tenant::factory()->create();
    $trainer = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['trainer'],
        'active_role' => 'trainer',
    ]);
    $member = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['spieler'],
    ]);

    $this->actingAs($trainer)
        ->patch(route('admin.users.roles.update', $member), [
            'roles' => ['verwaltung'],
        ])
        ->assertForbidden();

    expect($member->fresh()->roles)->toBe(['spieler']);
});
