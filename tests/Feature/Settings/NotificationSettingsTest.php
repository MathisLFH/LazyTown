<?php

use App\Models\User;

test('notification settings page is displayed with defaults', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notifications.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Notifications')
            ->where('notificationSettings.team_invitations', true)
            ->where('notificationSettings.club_announcements', true)
            ->has('categories.schedule_updates'),
        );
});

test('users can save and reload notification settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('notifications.update'), [
            'notification_settings' => [
                'team_invitations' => true,
                'team_changes' => false,
                'schedule_updates' => true,
                'club_announcements' => false,
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('notifications.edit'));

    expect($user->refresh()->notification_settings)->toBe([
        'team_invitations' => true,
        'team_changes' => false,
        'schedule_updates' => true,
        'club_announcements' => false,
    ]);
});

test('users can disable every notification category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('notifications.update'))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('notifications.edit'));

    expect($user->refresh()->notification_settings)->toBe([
        'team_invitations' => false,
        'team_changes' => false,
        'schedule_updates' => false,
        'club_announcements' => false,
    ]);
});
