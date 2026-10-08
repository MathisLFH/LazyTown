<?php

use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('authenticated users receive their notifications and unread count as shared data', function () {
    $user = User::factory()->create();

    DB::table('notifications')->insert([
        'id' => (string) Str::uuid(),
        'type' => 'team_invitation',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => json_encode([
            'title' => 'Neue Team-Einladung',
            'text' => 'Du wurdest eingeladen.',
            'url' => route('home'),
        ]),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('unreadNotificationsCount', 1)
            ->where('notifications.0.title', 'Neue Team-Einladung')
            ->where('notifications.0.url', route('home'))
            ->where('notifications.0.read_at', null),
        );
});

test('users can mark their unread notifications as read', function () {
    $user = User::factory()->create();

    DB::table('notifications')->insert([
        'id' => (string) Str::uuid(),
        'type' => 'team_invitation',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => json_encode(['title' => 'Test', 'text' => 'Test']),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('notifications.read'))
        ->assertRedirect();

    expect($user->fresh()->unreadNotifications)->toHaveCount(0);
});

test('users can mark one of their notifications as read', function () {
    $user = User::factory()->create();
    $notificationId = (string) Str::uuid();

    DB::table('notifications')->insert([
        'id' => $notificationId,
        'type' => 'test',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => json_encode(['title' => 'Test', 'text' => 'Test', 'url' => route('home')]),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('notifications.read.single', $notificationId))
        ->assertRedirect();

    expect($user->fresh()->notifications()->find($notificationId)->read_at)->not->toBeNull();
});

test('users cannot mark another users notification as read', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $notificationId = (string) Str::uuid();

    DB::table('notifications')->insert([
        'id' => $notificationId,
        'type' => 'test',
        'notifiable_type' => User::class,
        'notifiable_id' => $otherUser->id,
        'data' => json_encode(['title' => 'Test', 'text' => 'Test', 'url' => route('home')]),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('notifications.read.single', $notificationId))
        ->assertNotFound();

    expect($otherUser->fresh()->notifications()->find($notificationId)->read_at)->toBeNull();
});

test('users can create a local test notification', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notifications.test'))
        ->assertRedirect();

    expect($user->fresh()->notifications()->first()->type)
        ->toBe(TestNotification::class);
});
