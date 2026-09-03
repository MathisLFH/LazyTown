<?php

namespace App\Enums;

enum InvitationRole: string
{
    case Player = 'spieler';
    case Trainer = 'trainer';

    public function teamRole(): TeamRole
    {
        return match ($this) {
            self::Player => TeamRole::Member,
            self::Trainer => TeamRole::Admin,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Player => 'Spieler',
            self::Trainer => 'Trainer',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $role) => [
            'value' => $role->value,
            'label' => $role->label(),
        ], self::cases());
    }
}
