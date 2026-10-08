<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class NotificationDispatcher
{
    /**
     * Send a notification when the user enabled its category.
     */
    public function sendToUser(User $user, Notification $notification, string $category): bool
    {
        if (! ($user->notification_settings[$category] ?? true)) {
            return false;
        }

        $user->notify($notification);

        return true;
    }
}
