<?php

namespace App\Enums;

enum ConversationPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Normal => 'Normal',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Normal => 2,
            self::Medium => 3,
            self::High => 4,
            self::Urgent => 5,
        };
    }
}
