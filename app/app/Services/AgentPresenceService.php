<?php

namespace App\Services;

use App\Enums\AgentPresenceStatus;
use App\Models\AgentStatus;
use App\Models\User;

class AgentPresenceService
{
    public function touch(User $user): AgentStatus
    {
        return AgentStatus::updateOrCreate(
            ['user_id' => $user->id],
            ['last_seen_at' => now()]
        );
    }

    public function setStatus(User $user, AgentPresenceStatus $status): AgentStatus
    {
        return AgentStatus::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => $status->value,
                'last_seen_at' => now(),
            ]
        );
    }

    public function forUser(User $user): AgentStatus
    {
        return AgentStatus::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => AgentPresenceStatus::Online->value, 'last_seen_at' => now()]
        );
    }
}
