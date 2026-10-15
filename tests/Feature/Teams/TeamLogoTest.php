<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('trainers and administration can upload a logo visible to club members', function (TeamRole $membershipRole, string $userRole) {
    Storage::fake('public');

    $user = User::factory()->create([
        'roles' => [$userRole],
        'active_role' => $userRole,
    ]);
    $team = Team::factory()->create();
    $team->members()->attach($user, [
        'role' => $membershipRole->value,
        'status' => 'active',
    ]);
    $user->update(['current_team_id' => $team->id]);

    $this->actingAs($user)
        ->from(route('mein-team'))
        ->post(route('teams.logo.update', $team), [
            'logo' => UploadedFile::fake()->image('verein.png'),
        ])
        ->assertRedirect(route('mein-team'));

    $firstLogoPath = $team->refresh()->logo_path;
    Storage::disk('public')->assertExists($firstLogoPath);

    $this->actingAs($user)
        ->from(route('mein-team'))
        ->post(route('teams.logo.update', $team), [
            'logo' => UploadedFile::fake()->image('neues-verein.png'),
        ])
        ->assertRedirect(route('mein-team'));

    $team->refresh();
    Storage::disk('public')->assertMissing($firstLogoPath);
    Storage::disk('public')->assertExists($team->logo_path);
    $logoUrl = Storage::disk('public')->url($team->logo_path);

    $this->actingAs($user)
        ->get(route('mein-team'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('MeinTeam')
            ->where('team.logoUrl', $logoUrl)
            ->where('permissions.canUpdateTeam', true),
        );

    $player = User::factory()->create([
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);
    $team->members()->attach($player, [
        'role' => TeamRole::Member->value,
        'status' => 'active',
    ]);
    $player->update(['current_team_id' => $team->id]);

    $this->actingAs($player)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Startseite')
            ->where('clubLogoUrl', $logoUrl),
        );
})->with([
    'trainer' => [TeamRole::Owner, 'trainer'],
    'administration' => [TeamRole::Admin, 'admin'],
]);

test('players cannot upload a club logo', function () {
    Storage::fake('public');

    $player = User::factory()->create([
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);
    $team = Team::factory()->create();
    $team->members()->attach($player, [
        'role' => TeamRole::Member->value,
        'status' => 'active',
    ]);
    $player->update(['current_team_id' => $team->id]);
    $logoPath = 'team-logos/'.$team->id.'/verein.png';
    $team->update(['logo_path' => $logoPath]);

    $this->actingAs($player)
        ->from(route('mein-team'))
        ->post(route('teams.logo.update', $team), [
            'logo' => UploadedFile::fake()->image('verein.png'),
        ])
        ->assertForbidden();

    expect($team->fresh()->logo_path)->toBe($logoPath)
        ->and(Storage::disk('public')->allFiles())->toBe([]);

    $this->actingAs($player)
        ->get(route('mein-team'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('MeinTeam')
            ->where('team.logoUrl', null)
            ->where('permissions.canUpdateTeam', false),
        );
});

test('members cannot upload a logo for a different club', function () {
    Storage::fake('public');

    $player = User::factory()->create(['roles' => ['spieler'], 'active_role' => 'spieler']);
    $ownTeam = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $ownTeam->members()->attach($player, [
        'role' => TeamRole::Member->value,
        'status' => 'active',
    ]);
    $player->update(['current_team_id' => $ownTeam->id]);

    $this->actingAs($player)
        ->post(route('teams.logo.update', $otherTeam), [
            'logo' => UploadedFile::fake()->image('verein.png'),
        ])
        ->assertNotFound();

    expect($otherTeam->fresh()->logo_path)->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('logo upload requires a file', function () {
    Storage::fake('public');

    $trainer = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);
    $team = Team::factory()->create();
    $team->members()->attach($trainer, [
        'role' => TeamRole::Owner->value,
        'status' => 'active',
    ]);

    $this->actingAs($trainer)
        ->from(route('mein-team'))
        ->post(route('teams.logo.update', $team))
        ->assertSessionHasErrors(['logo' => 'Bitte wähle ein Vereinslogo aus.']);

    expect($team->fresh()->logo_path)->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('logo upload rejects unsupported image formats', function () {
    Storage::fake('public');

    $trainer = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);
    $team = Team::factory()->create();
    $team->members()->attach($trainer, [
        'role' => TeamRole::Owner->value,
        'status' => 'active',
    ]);

    $this->actingAs($trainer)
        ->from(route('mein-team'))
        ->post(route('teams.logo.update', $team), [
            'logo' => UploadedFile::fake()->create('logo.txt', 100, 'text/plain'),
        ])
        ->assertSessionHasErrors('logo');

    expect(session('errors')->getBag('default')->first('logo'))->toBe('Das Vereinslogo muss eine Bilddatei sein.')
        ->and($team->fresh()->logo_path)->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('logo upload rejects images larger than 2 MB', function () {
    Storage::fake('public');

    $trainer = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);
    $team = Team::factory()->create();
    $team->members()->attach($trainer, [
        'role' => TeamRole::Owner->value,
        'status' => 'active',
    ]);

    $this->actingAs($trainer)
        ->from(route('mein-team'))
        ->post(route('teams.logo.update', $team), [
            'logo' => UploadedFile::fake()->image('logo.png')->size(2049),
        ])
        ->assertSessionHasErrors('logo');

    expect(session('errors')->getBag('default')->first('logo'))->toBe('Das Vereinslogo darf höchstens 2 MB groß sein.')
        ->and($team->fresh()->logo_path)->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('guests are redirected before they can upload a club logo', function () {
    $team = Team::factory()->create();

    $this->post(route('teams.logo.update', $team), [
        'logo' => UploadedFile::fake()->image('verein.png'),
    ])->assertRedirect(route('login'));
});
