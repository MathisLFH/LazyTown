<?php

use App\Enums\TeamRole;
use App\Models\Gym;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;

it('redirects guests to login', function () {
    $this->get(route('search.suggestions'))
        ->assertRedirect(route('login'));
});

it('returns no groups for short queries', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('search.suggestions', ['q' => 'a']))
        ->assertOk()
        ->assertExactJson([
            'query' => 'a',
            'groups' => [],
        ]);
});

it('rejects queries longer than one hundred characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('search.suggestions', ['q' => str_repeat('a', 101)]))
        ->assertUnprocessable();
});

it('returns users and teams from the administrators tenant only', function () {
    $tenant = Tenant::factory()->create();
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $team = Team::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Senioren',
    ]);
    $team->members()->attach($administrator, [
        'tenant_id' => $tenant->id,
        'role' => TeamRole::Owner->value,
    ]);
    $administrator->switchTeam($team);
    User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lukas Müller',
    ]);
    User::factory()->create([
        'tenant_id' => Tenant::factory()->create()->id,
        'name' => 'Lukas Fremdverein',
    ]);

    $response = $this->actingAs($administrator)
        ->getJson(route('search.suggestions', ['q' => 'Lukas']));

    $response
        ->assertOk()
        ->assertJsonPath('groups.0.type', 'user')
        ->assertJsonFragment(['title' => 'Lukas Müller'])
        ->assertJsonMissing(['title' => 'Lukas Fremdverein']);
});

it('returns teams available to the current user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $team = Team::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Senioren',
    ]);
    $team->members()->attach($user, [
        'tenant_id' => $tenant->id,
        'role' => TeamRole::Owner->value,
    ]);
    $user->switchTeam($team);

    $this->actingAs($user)
        ->getJson(route('search.suggestions', ['q' => 'Senioren']))
        ->assertOk()
        ->assertJsonPath('groups.0.type', 'team')
        ->assertJsonFragment(['title' => 'Senioren']);
});

it('returns halls from the current tenant only', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $team = Team::factory()->create(['tenant_id' => $tenant->id]);
    $team->members()->attach($user, [
        'tenant_id' => $tenant->id,
        'role' => TeamRole::Owner->value,
    ]);
    $user->switchTeam($team);
    Gym::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Sporthalle Mitte',
    ]);
    Gym::factory()->create([
        'tenant_id' => Tenant::factory()->create()->id,
        'name' => 'Sporthalle Fremdverein',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('search.suggestions', ['q' => 'Sporthalle']));

    $response
        ->assertOk()
        ->assertJsonPath('groups.0.type', 'gym')
        ->assertJsonFragment(['title' => 'Sporthalle Mitte'])
        ->assertJsonMissing(['title' => 'Sporthalle Fremdverein']);
});

it('returns security settings for a club member', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $team = Team::factory()->create(['tenant_id' => $tenant->id]);
    $team->members()->attach($user, [
        'tenant_id' => $tenant->id,
        'role' => TeamRole::Owner->value,
    ]);
    $user->switchTeam($team);

    $this->actingAs($user)
        ->getJson(route('search.suggestions', ['q' => 'Sicherheit']))
        ->assertOk()
        ->assertJsonPath('groups.0.type', 'setting')
        ->assertJsonFragment(['title' => 'Sicherheit']);
});

it('returns settings allowed for the current users role', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $team = Team::factory()->create(['tenant_id' => $tenant->id]);
    $team->members()->attach($user, [
        'tenant_id' => $tenant->id,
        'role' => TeamRole::Owner->value,
    ]);
    $user->switchTeam($team);

    $response = $this->actingAs($user)
        ->getJson(route('search.suggestions', ['q' => 'verwaltung']));

    $response
        ->assertOk()
        ->assertJsonMissing(['title' => 'Teams verwalten']);
});
