<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

test('trainers can open club onboarding before payment', function () {
    $user = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);

    $this->actingAs($user)
        ->get(route('club.onboarding'))
        ->assertOk();
});

test('team owners can complete the payment form', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $response = $this->actingAs($user)->post(route('teams.payment.update', $team), [
        'cardholder' => 'Max Mustermann',
        'card_number' => '4242424242424242',
        'expiry' => '12/30',
        'cvc' => '123',
    ]);

    $response->assertRedirect(route('teams.edit', $team));
    $this->assertDatabaseHas('teams', ['id' => $team->id, 'payment_status' => 'paid']);
    expect($user->refresh()->roles)->toContain('trainer')
        ->and($user->active_role)->toBe('trainer');
});

test('club onboarding creates an unowned pending team until payment', function () {
    $user = User::factory()->create(['roles' => ['trainer'], 'active_role' => 'trainer']);

    $response = $this->actingAs($user)->post(route('club.onboarding.store'), ['name' => 'New Club']);
    $team = Team::where('name', 'New Club')->firstOrFail();

    $response->assertRedirect(route('teams.payment.edit', $team));
    expect($team->members()->count())->toBe(0);

    $this->actingAs($user)->post(route('teams.payment.skip', $team));

    expect($user->refresh()->roles)->toContain('trainer')
        ->and($team->fresh()->members()->whereKey($user->id)->exists())->toBeTrue();
});

test('team owners can skip payment temporarily', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user)
        ->post(route('teams.payment.skip', $team))
        ->assertRedirect(route('teams.edit', $team));

    $this->assertDatabaseHas('teams', ['id' => $team->id, 'payment_status' => 'skipped']);
});

test('team members cannot process payment', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->actingAs($user)
        ->post(route('teams.payment.skip', $team))
        ->assertForbidden();
});
