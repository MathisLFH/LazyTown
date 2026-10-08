<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $categories = config('notifications.categories');
        $settings = array_replace(
            $this->defaultSettings($categories),
            $request->user()->notification_settings ?? [],
        );

        return Inertia::render('settings/Notifications', [
            'categories' => $categories,
            'notificationSettings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $categories = config('notifications.categories');

        $validated = $request->validate([
            'notification_settings' => ['nullable', 'array'],
            'notification_settings.*' => ['boolean'],
        ]);

        $settings = [];

        foreach (array_keys($categories) as $key) {
            $settings[$key] = (bool) ($validated['notification_settings'][$key] ?? false);
        }

        $request->user()->update(['notification_settings' => $settings]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Benachrichtigungseinstellungen gespeichert.',
        ]);

        return to_route('notifications.edit');
    }

    /**
     * @param  array<string, array{label: string, description: string}>  $categories
     * @return array<string, bool>
     */
    private function defaultSettings(array $categories): array
    {
        return array_fill_keys(array_keys($categories), true);
    }
}
