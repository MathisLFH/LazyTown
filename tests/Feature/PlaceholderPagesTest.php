<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the public start page renders the dashboard placeholder', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});

test('authenticated users can open the placeholder pages', function () {
    $user = User::factory()->create([
        'roles' => ['spieler', 'trainer', 'verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user);

    foreach ([
        'spielplan',
        'hallenplan',
        'mein-team',
        'profil',
        'paesse-beantragen',
        'spielende-hinzufuegen',
        'mannschaft-bearbeiten',
        'hallenplan-bearbeiten',
        'bezahlung',
    ] as $routeName) {
        $this->get(route($routeName))
            ->assertOk();
    }
});

test('users without the required role see the permission page', function () {
    $user = User::factory()->create([
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->actingAs($user)
        ->get(route('hallenplan-bearbeiten'))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/PermissionDenied')
            ->where('requiredRole', 'verwaltung'));
});

test('trainers can access trainer pages but not administration pages', function () {
    $user = User::factory()->create([
        'roles' => ['trainer'],
        'active_role' => 'trainer',
    ]);
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user);

    $this->get(route('mannschaft-bearbeiten'))->assertOk();
    $this->get(route('bezahlung'))->assertForbidden();
});

test('authenticated users can log out through fortify', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();
});

test('authenticated users can export their safe profile data', function () {
    $user = User::factory()->create([
        'name' => 'Max Mustermann',
    ]);

    $response = $this->actingAs($user)->get(route('profile.export'));

    $response->assertOk()
        ->assertDownload('lazytown-datenexport.json');

    $export = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);

    expect($export['profile']['name'])->toBe('Max Mustermann')
        ->and($export['profile'])->not->toHaveKey('password')
        ->and($export['profile'])->not->toHaveKey('remember_token');
});
