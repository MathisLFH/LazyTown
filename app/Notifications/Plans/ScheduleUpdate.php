<?php

namespace App\Notifications\Plans;

use Illuminate\Notifications\Notification;

class ScheduleUpdate extends Notification
{
    public const CATEGORY = 'schedule_updates';

    public function __construct(
        public readonly string $plan,
        public readonly string $teamName,
        public readonly string $url,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "{$this->plan} geändert",
            'text' => "Der {$this->plan} für {$this->teamName} wurde geändert.",
            'url' => $this->url,
            'category' => self::CATEGORY,
        ];
    }
}
