<?php

namespace App\Enums;

enum AgentPresenceStatus: string
{
    case Online = 'online';
    case Away = 'away';
    case Busy = 'busy';
    case Offline = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Away => 'Away',
            self::Busy => 'Busy',
            self::Offline => 'Offline',
        };
    }

    public function acceptsChats(): bool
    {
        return $this === self::Online;
    }
}
