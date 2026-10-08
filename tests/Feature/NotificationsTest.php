<?php

use App\Models\User;
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
