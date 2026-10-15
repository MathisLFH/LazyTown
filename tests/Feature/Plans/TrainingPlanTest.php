<?php

use App\Enums\TeamRole;
use App\Models\Gym;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\Training;
use App\Models\User;
use App\Notifications\Plans\ScheduleUpdate;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

function createTrainingTrainer(): array
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

test('training plan shows only own tenant data and relevant gyms', function () {
    [$trainer, $tenant, $team] = createTrainingTrainer();
    $otherTenant = Tenant::factory()->create();
    $otherTeam = Team::factory()->create(['tenant_id' => $otherTenant->id]);
    $sharedGym = Gym::factory()->create(['tenant_id' => null, 'name' => 'Global Gym']);
    $ownGym = Gym::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Own Gym']);
    Gym::factory()->create(['tenant_id' => $otherTenant->id, 'name' => 'Other Gym']);

    Training::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'gym_id' => $sharedGym->id,
        'weekday' => 'Montag',
        'starts_at' => '18:00:00',
        'ends_at' => '19:00:00',
    ]);
    Training::factory()->create([
        'tenant_id' => $otherTenant->id,
        'team_id' => $otherTeam->id,
        'weekday' => 'Montag',
        'starts_at' => '18:00:00',
        'ends_at' => '19:00:00',
    ]);

    $response = $this->actingAs($trainer)
        ->get(route('hallenplan'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canEdit', true)
            ->has('trainings', 1)
        );

    $gyms = collect($response->viewData('page')['props']['gyms'])->pluck('name')->all();

    expect($gyms)
        ->toContain('Global Gym')
        ->toContain('Own Gym')
        ->not->toContain('Other Gym');
});

test('non trainers can not write training plans', function () {
    [, $tenant, $team] = createTrainingTrainer();
    $player = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);
    $administrator = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['verwaltung'],
        'active_role' => 'verwaltung',
    ]);
    $team->members()->attach($player, ['role' => TeamRole::Member->value, 'status' => 'active']);
    $team->members()->attach($administrator, ['role' => TeamRole::Owner->value, 'status' => 'active']);
    $player->update(['current_team_id' => $team->id]);
    $administrator->update(['current_team_id' => $team->id]);

    $this->actingAs($player)
        ->post(route('hallenplan.csv'), ['file' => UploadedFile::fake()->create('hallen.csv')])
        ->assertForbidden();

    $this->actingAs($player)
        ->put(route('hallenplan.sync'), ['rows' => []])
        ->assertForbidden();

    $this->actingAs($administrator)
        ->put(route('hallenplan.sync'), ['rows' => []])
        ->assertForbidden();
});

test('trainer sync creates gyms and keeps non manageable trainings untouched', function () {
    [$trainer, $tenant, $team] = createTrainingTrainer();
    $otherTeam = Team::factory()->create(['tenant_id' => $tenant->id, 'name' => 'U17']);
    $otherTraining = Training::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $otherTeam->id,
        'weekday' => 'Dienstag',
    ]);

    $this->actingAs($trainer)
        ->put(route('hallenplan.sync'), [
            'rows' => [[
                'team_id' => $team->id,
                'gym_name' => 'Neue Halle',
                'weekday' => 'Montag',
                'starts_at' => '18:00',
                'ends_at' => '19:30',
            ]],
        ])
        ->assertRedirect(route('hallenplan'));

    $this->assertDatabaseHas('gyms', [
        'tenant_id' => $tenant->id,
        'name' => 'Neue Halle',
    ]);
    $this->assertDatabaseHas('trainings', [
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'weekday' => 'Montag',
    ]);
    $this->assertDatabaseHas('trainings', ['id' => $otherTraining->id]);
});

test('trainer sync notifies active players when a training changes', function () {
    [$trainer, $tenant, $team] = createTrainingTrainer();
    $player = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);
    $team->members()->attach($player, ['role' => TeamRole::Member->value, 'status' => 'active']);
    $training = Training::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'weekday' => 'Montag',
        'starts_at' => '18:00:00',
        'ends_at' => '19:30:00',
    ]);

    $this->actingAs($trainer)
        ->put(route('hallenplan.sync'), [
            'rows' => [[
                'id' => $training->id,
                'team_id' => $team->id,
                'gym_name' => $training->gym->name,
                'weekday' => 'Montag',
                'starts_at' => '18:30',
                'ends_at' => '19:30',
            ]],
        ])
        ->assertRedirect(route('hallenplan'));

    $notification = $player->fresh()->notifications()->first();

    expect($notification)->not->toBeNull();
    expect($notification->type)->toBe(ScheduleUpdate::class);
    expect($notification->data)->toMatchArray([
        'title' => 'Trainingsplan geändert',
        'text' => 'Der Trainingsplan für Herren 1 wurde geändert.',
        'url' => route('hallenplan'),
        'category' => 'schedule_updates',
    ]);
});

test('trainer sync does not notify players when the training plan is unchanged', function () {
    [$trainer, $tenant, $team] = createTrainingTrainer();
    $player = User::factory()->create([
        'tenant_id' => $tenant->id,
        'roles' => ['spieler'],
        'active_role' => 'spieler',
    ]);
    $team->members()->attach($player, ['role' => TeamRole::Member->value, 'status' => 'active']);
    $training = Training::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'weekday' => 'Montag',
        'starts_at' => '18:00:00',
        'ends_at' => '19:30:00',
    ]);

    $this->actingAs($trainer)
        ->put(route('hallenplan.sync'), [
            'rows' => [[
                'id' => $training->id,
                'team_id' => $team->id,
                'gym_name' => $training->gym->name,
                'weekday' => 'Montag',
                'starts_at' => '18:00',
                'ends_at' => '19:30',
            ]],
        ])
        ->assertRedirect(route('hallenplan'));

    expect($player->fresh()->notifications)->toHaveCount(0);
});

test('training validation rejects invalid times and non manageable teams', function () {
    [$trainer, $tenant, $team] = createTrainingTrainer();
    $otherTeam = Team::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($trainer)
        ->put(route('hallenplan.sync'), [
            'rows' => [[
                'team_id' => $team->id,
                'gym_name' => 'Halle',
                'weekday' => 'Montag',
                'starts_at' => '19:00',
                'ends_at' => '18:00',
            ]],
        ])
        ->assertSessionHasErrors(['rows.0.ends_at']);

    $this->actingAs($trainer)
        ->put(route('hallenplan.sync'), [
            'rows' => [[
                'team_id' => $otherTeam->id,
                'gym_name' => 'Halle',
                'weekday' => 'Montag',
                'starts_at' => '18:00',
                'ends_at' => '19:00',
            ]],
        ])
        ->assertSessionHasErrors(['rows.0.team_id']);
});

test('training csv parser resolves team names against manageable teams', function () {
    [$trainer, , $team] = createTrainingTrainer();
    $csv = "Wochentag;Beginn;Ende;Halle;Mannschaft\nMontag;18:00;19:30;Nordhalle;Herren 1\n";
    $file = UploadedFile::fake()->createWithContent('hallen.csv', $csv);

    $response = $this->actingAs($trainer)
        ->post(route('hallenplan.csv'), ['file' => $file])
        ->assertOk();

    $response->assertJsonPath('rows.0.team_id', $team->id);
    $response->assertJsonPath('rows.0.gym_name', 'Nordhalle');
});
