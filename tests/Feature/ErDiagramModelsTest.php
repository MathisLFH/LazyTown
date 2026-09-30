<?php

use App\Enums\TeamRole;
use App\Models\ClubMatch;
use App\Models\ClubRole;
use App\Models\Gym;
use App\Models\Message;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\Training;
use App\Models\User;

test('tenants relate their administrators, users, and teams', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $team = Team::factory()->create(['tenant_id' => $tenant->id]);
    $tenant->update(['admin_id' => $admin->id]);

    expect($tenant->fresh()->admin->is($admin))->toBeTrue()
        ->and($tenant->users->contains($admin))->toBeTrue()
        ->and($tenant->teams->contains($team))->toBeTrue()
        ->and($team->tenant->is($tenant))->toBeTrue();
});

test('trainings and matches belong to a tenant team and gym', function () {
    $tenant = Tenant::factory()->create();
    $team = Team::factory()->create(['tenant_id' => $tenant->id]);
    $gym = Gym::factory()->create();
    $training = Training::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'gym_id' => $gym->id,
    ]);
    $match = ClubMatch::factory()->create([
        'tenant_id' => $tenant->id,
        'team_id' => $team->id,
        'at_home' => true,
    ]);

    expect($training->tenant->is($tenant))->toBeTrue()
        ->and($training->team->is($team))->toBeTrue()
        ->and($training->gym->is($gym))->toBeTrue()
        ->and($team->trainings->contains($training))->toBeTrue()
        ->and($match->tenant->is($tenant))->toBeTrue()
        ->and($match->team->is($team))->toBeTrue()
        ->and($match->at_home)->toBeTrue();
});

test('messages and subscriptions can be assigned to users and tenants', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $message = Message::factory()->create(['tenant_id' => $tenant->id]);
    $subscription = Subscription::factory()->create();

    $message->users()->attach($user);
    $tenant->subscriptions()->attach($subscription, [
        'subscription_time' => now(),
        'payment_type' => 'card',
    ]);

    expect($tenant->messages->contains($message))->toBeTrue()
        ->and($user->messages->contains($message))->toBeTrue()
        ->and($tenant->subscriptions->contains($subscription))->toBeTrue()
        ->and($tenant->subscriptions->first()->pivot->payment_type)->toBe('card');
});

test('users and memberships can reference the shared club roles table', function () {
    $tenant = Tenant::factory()->create();
    $team = Team::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $role = ClubRole::factory()->create(['team_id' => $team->id]);

    $user->update(['role_id' => $role->id]);
    $team->members()->attach($user, [
        'tenant_id' => $tenant->id,
        'role_id' => $role->id,
        'role' => TeamRole::Member->value,
    ]);

    $membership = $team->memberships()->firstOrFail();

    expect($user->fresh()->role->is($role))->toBeTrue()
        ->and($membership->clubRole->is($role))->toBeTrue()
        ->and($membership->tenant->is($tenant))->toBeTrue();
});
