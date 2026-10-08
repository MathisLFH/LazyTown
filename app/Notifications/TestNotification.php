<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class TestNotification extends Notification
{
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Testbenachrichtigung',
            'text' => 'Diese Benachrichtigung wurde zum Testen des Notification-Dropdowns erstellt.',
            'url' => route('home'),
            'category' => 'test',
        ];
    }
}
