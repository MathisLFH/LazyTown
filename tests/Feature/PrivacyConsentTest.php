<?php

use App\Models\User;

test('users without privacy consent are redirected to the consent page', function () {
    $user = User::factory()->create([
        'privacy_policy_version' => null,
        'privacy_policy_accepted_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('privacy.consent.edit'));
});

test('users with an outdated privacy consent are redirected to the consent page', function () {
    $user = User::factory()->create([
        'privacy_policy_version' => '0.9',
        'privacy_policy_accepted_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('privacy.consent.edit'));
});

test('users can accept the current privacy policy', function () {
    $user = User::factory()->create([
        'privacy_policy_version' => null,
        'privacy_policy_accepted_at' => null,
    ]);

    $this->actingAs($user)
        ->post(route('privacy.consent.update'), [
            'privacy_consent' => '1',
        ])
        ->assertRedirect(route('home'));

    expect($user->refresh()->privacy_policy_version)->toBe(config('privacy.policy_version'))
        ->and($user->privacy_policy_accepted_at)->not->toBeNull();
});

test('privacy consent requires an explicit accepted value', function () {
    $user = User::factory()->create([
        'privacy_policy_version' => null,
        'privacy_policy_accepted_at' => null,
    ]);

    $this->actingAs($user)
        ->from(route('privacy.consent.edit'))
        ->post(route('privacy.consent.update'), [
            'privacy_consent' => '0',
        ])
        ->assertRedirect(route('privacy.consent.edit'))
        ->assertSessionHasErrors('privacy_consent');

    expect($user->refresh()->privacy_policy_accepted_at)->toBeNull();
});
