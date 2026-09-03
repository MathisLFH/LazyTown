<?php

namespace App\Enums;

enum UserRole: string
{
    case Player = 'spieler';
    case Coach = 'trainer';
    case Administration = 'verwaltung';

    public function label(): string
    {
        return match ($this) {
            self::Player => 'Spieler',
            self::Coach => 'Trainer',
            self::Administration => 'Verwaltung',
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
