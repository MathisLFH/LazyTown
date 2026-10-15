<?php

namespace App\Services\Notifications;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Notifications\Plans\ScheduleUpdate;

class ScheduleUpdateNotifier
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    public function notifyTeamPlayers(Team $team, string $plan, string $url): void
    {
        $notification = new ScheduleUpdate($plan, $team->name, $url);

        $players = $team->members()
            ->wherePivot('status', 'active')
            ->wherePivot('role', TeamRole::Member->value)
            ->get();

        foreach ($players as $player) {
            $this->dispatcher->sendToUser(
                $player,
                $notification,
                ScheduleUpdate::CATEGORY,
            );
        }
    }
}
