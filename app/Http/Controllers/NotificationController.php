<?php

namespace App\Http\Controllers;

use App\Notifications\TestNotification;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function __construct(private NotificationDispatcher $notificationDispatcher) {}

    /**
     * Mark all notifications for the current user as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    /**
     * Mark one notification belonging to the current user as read.
     */
    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return back();
    }

    /**
     * Create a database notification for testing the notification dropdown.
     */
    public function createTest(Request $request): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $this->notificationDispatcher->sendToUser(
            $request->user(),
            new TestNotification,
            TestNotification::CATEGORY,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Testbenachrichtigung erstellt.',
        ]);

        return back();
    }
}
