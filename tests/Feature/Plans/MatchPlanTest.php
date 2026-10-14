<?php

use App\Enums\TeamRole;
use App\Models\ClubMatch;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Plans\ScheduleUpdate;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

function createTrainerWithTeamForPlans(): array
{
    $tenant = Tenant::factory()->create();
    $team = Team::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Herren 1',
        'is_personal' => false,
    ]);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['trainer'],
        'active_role' => 'trainer',
    ]);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value, 'status' => 'active']);
    $user->update(['current_team_id' => $team->id]);

    return [$user, $tenant, $team];
}

test('players only see matches from their tenant and can not edit', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $team = Team::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Damen 1']);
    $otherTeam = Team::factory()->create(['tenant_id' => $otherTenant->id, 'name' => 'Fremd']);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);
    $team->members()->attach($user, ['role' => TeamRole::Member->value, 'status' => 'active']);
    $user->update(['current_team_id' => $team->id]);

    $visibleMatch = ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'season' => '2026/27',
        'versus' => 'Current opponent',
        'city' => 'Current city',
    ]);
    ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'season' => '2024/25',
        'versus' => 'Historical opponent',
        'city' => 'Historical city',
    ]);
    ClubMatch::factory()->create([
        'tenant_id' => $otherTenant->id,
        'team_id' => $otherTeam->id,
        'season' => '2026/27',
        'versus' => 'Private opponent',
        'city' => 'Private city',
    ]);

    $this->actingAs($user)
        ->get(route('spielplan'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canEdit', false)
            ->has('matches', 1)
            ->where('matches.0.id', $visibleMatch->id)
            ->has('suggestions.opponents', 2)
            ->where('suggestions.opponents.1', 'Historical opponent')
            ->has('suggestions.cities', 2)
            ->where('suggestions.cities.1', 'Historical city'),
        );
});

test('players can not write to match plan endpoints', function () {
    [$trainer, , $team] = createTrainerWithTeamForPlans();
    $player = User::factory()->create([
        'tenant_id' => $trainer->tenant_id,
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);
    $team->members()->attach($player, ['role' => TeamRole::Member->value, 'status' => 'active']);
    $player->update(['current_team_id' => $team->id]);

    $this->actingAs($player)
        ->post(route('spielplan.csv'), ['file' => UploadedFile::fake()->create('plan.csv')])
        ->assertForbidden();

    $this->actingAs($player)
        ->put(route('spielplan.sync'), [
            'team_id' => $team->id,
            'season' => '2026/27',
            'rows' => [],
        ])
        ->assertForbidden();
});

test('trainer sync creates updates and deletes matches in one request', function () {
    [$trainer, $tenant, $team] = createTrainerWithTeamForPlans();
    $disabledPlayer = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['spieler'],
        'active_role' => 'spieler',
        'notification_settings' => ['schedule_updates' => false],
    ]);
    $player = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);
    $pendingPlayer = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);
    $otherTeam = Team::factory()->create(['tenant_id' => $tenant->id]);
    $otherTeamPlayer = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);
    $team->members()->attach($disabledPlayer, ['role' => TeamRole::Member->value, 'status' => 'active']);
    $team->members()->attach($player, ['role' => TeamRole::Member->value, 'status' => 'active']);
    $team->members()->attach($pendingPlayer, ['role' => TeamRole::Member->value, 'status' => 'pending']);
    $otherTeam->members()->attach($otherTeamPlayer, ['role' => TeamRole::Member->value, 'status' => 'active']);

    $toUpdate = ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'season' => '2026/27',
        'date' => '2026-09-15',
        'versus' => 'Alt',
    ]);
    $toDelete = ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'season' => '2026/27',
    ]);

    $this->actingAs($trainer)
        ->put(route('spielplan.sync'), [
            'team_id' => $team->id,
            'season' => '2026/27',
            'rows' => [
                [
                    'id' => $toUpdate->id,
                    'date' => '2026-09-15',
                    'versus' => 'Neu',
                    'at_home' => true,
                    'city' => 'Löningen',
                ],
                [
                    'date' => '2026-10-01',
                    'versus' => 'TSV Süd',
                    'at_home' => false,
                    'city' => 'Oldenburg',
                ],
            ],
        ])
        ->assertRedirect(route('spielplan', ['season' => '2026/27']));

    $this->assertDatabaseMissing('matches', ['id' => $toDelete->id]);
    $this->assertDatabaseHas('matches', [
        'id' => $toUpdate->id,
        'versus' => 'Neu',
        'tenant_id' => $tenant->id,
    ]);
    $this->assertDatabaseHas('matches', [
        'team_id' => $team->id,
        'tenant_id' => $tenant->id,
        'season' => '2026/27',
        'versus' => 'TSV Süd',
    ]);

    $notification = $player->fresh()->notifications()->first();

    expect($notification)->not->toBeNull();
    expect($notification->type)->toBe(ScheduleUpdate::class);
    expect($notification->data)->toMatchArray([
        'title' => 'Spielplan geändert',
        'text' => 'Der Spielplan für Herren 1 wurde geändert.',
        'url' => route('spielplan', ['season' => '2026/27']),
        'category' => 'schedule_updates',
    ]);
    expect($pendingPlayer->fresh()->notifications)->toHaveCount(0);
    expect($disabledPlayer->fresh()->notifications)->toHaveCount(0);
    expect($otherTeamPlayer->fresh()->notifications)->toHaveCount(0);

    $this->actingAs($trainer)
        ->get(route('spielplan', ['season' => '2026/27']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('season', '2026/27')
            ->has('matches', 2)
            ->where('matches.0.versus', 'Neu')
            ->where('matches.1.versus', 'TSV Süd'),
        );
});

test('trainer sync does not notify players when the match plan is unchanged', function () {
    [$trainer, $tenant, $team] = createTrainerWithTeamForPlans();
    $player = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);
    $team->members()->attach($player, ['role' => TeamRole::Member->value, 'status' => 'active']);
    $match = ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'season' => '2026/27',
        'date' => '2026-09-15',
        'versus' => 'Gegner',
        'at_home' => false,
        'city' => null,
    ]);

    $this->actingAs($trainer)
        ->put(route('spielplan.sync'), [
            'team_id' => $team->id,
            'season' => '2026/27',
            'rows' => [[
                'id' => $match->id,
                'date' => '2026-09-15',
                'versus' => 'Gegner',
                'at_home' => false,
                'city' => null,
            ]],
        ])
        ->assertRedirect(route('spielplan', ['season' => '2026/27']));

    expect($player->fresh()->notifications)->toHaveCount(0);
});

test('administration can edit match plans like trainers', function () {
    $tenant = Tenant::factory()->create();
    $team = Team::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Herren 1',
        'is_personal' => false,
    ]);
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $team->members()->attach($administrator, ['role' => TeamRole::Owner->value, 'status' => 'active']);
    $administrator->update(['current_team_id' => $team->id]);

    $this->actingAs($administrator)
        ->get(route('spielplan'))
        ->assertInertia(fn (Assert $page) => $page->where('canEdit', true));

    $this->actingAs($administrator)
        ->post(route('spielplan.csv'), [
            'file' => UploadedFile::fake()->createWithContent(
                'spielplan.csv',
                "Datum;Mannschaft 1;Mannschaft 2;Ort;Saison\n2026-09-15;Herren 1;TSV Nord;Oldenburg;2026/27\n",
            ),
        ])
        ->assertOk();

    $this->actingAs($administrator)
        ->put(route('spielplan.sync'), [
            'team_id' => $team->id,
            'season' => '2026/27',
            'rows' => [[
                'date' => '2026-09-15',
                'versus' => 'TSV Nord',
                'at_home' => true,
                'city' => 'Oldenburg',
            ]],
        ])
        ->assertRedirect(route('spielplan', ['season' => '2026/27']));

    $this->assertDatabaseHas('matches', [
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'season' => '2026/27',
        'versus' => 'TSV Nord',
    ]);
});

test('trainer can not sync non manageable team and can not update foreign ids', function () {
    [$trainer, $tenant, $team] = createTrainerWithTeamForPlans();
    $otherTeam = Team::factory()->create(['tenant_id' => $tenant->id, 'name' => 'U19']);
    $foreignMatch = ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $otherTeam->id,
        'season' => '2026/27',
    ]);

    $this->actingAs($trainer)
        ->put(route('spielplan.sync'), [
            'team_id' => $otherTeam->id,
            'season' => '2026/27',
            'rows' => [],
        ])
        ->assertForbidden();

    $this->actingAs($trainer)
        ->put(route('spielplan.sync'), [
            'team_id' => $team->id,
            'season' => '2026/27',
            'rows' => [[
                'id' => $foreignMatch->id,
                'date' => '2026-09-20',
                'versus' => 'ABC',
                'at_home' => true,
                'city' => 'Test',
            ]],
        ])
        ->assertSessionHasErrors(['rows.0.id']);
});

test('match csv parser handles cp1252 data and reports invalid lines', function () {
    [$trainer] = createTrainerWithTeamForPlans();
    $csvUtf8 = "Datum;Mannschaft 1;Mannschaft 2;Ort;Saison;Uhrzeit\n"
        ."12.09.26;Herren 1;VfL Löningen III;Löningen;2026/27;18.30\n"
        ."13.09.26;Herren 1;;Löningen;2026/27;\n";
    $csvCp1252 = mb_convert_encoding($csvUtf8, 'Windows-1252', 'UTF-8');
    $file = UploadedFile::fake()->createWithContent('spielplan.csv', $csvCp1252);

    $response = $this->actingAs($trainer)
        ->post(route('spielplan.csv'), ['file' => $file])
        ->assertOk();

    $response->assertJsonPath('rows.0.city', 'Löningen');
    $response->assertJsonPath('rows.0.date', '2026-09-12');
    $response->assertJsonPath('rows.0.kickoff_time', '18:30');
    $response->assertJsonCount(1, 'errors');
});

test('match sync validation rejects malformed input and duplicate rows', function () {
    [$trainer, , $team] = createTrainerWithTeamForPlans();

    $this->actingAs($trainer)
        ->put(route('spielplan.sync'), [
            'team_id' => $team->id,
            'season' => '2026/27',
            'rows' => [
                [
                    'date' => '2026-09-12',
                    'versus' => 'A',
                    'at_home' => true,
                    'city' => 'X',
                ],
                [
                    'date' => '2026-09-12',
                    'versus' => 'A',
                    'at_home' => true,
                    'city' => 'Y',
                ],
            ],
        ])
        ->assertSessionHasErrors(['rows.1.date']);

    $this->actingAs($trainer)
        ->put(route('spielplan.sync'), [
            'team_id' => $team->id,
            'season' => '2026/27',
            'rows' => [[
                'date' => '12-09-2026',
                'versus' => 'A',
                'at_home' => true,
                'city' => 'X',
            ]],
        ])
        ->assertSessionHasErrors(['rows.0.date']);
});
