<?php

namespace App\Models;

use App\Enums\AgentPresenceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentStatus extends Model
{
    protected $fillable = [
        'user_id', 'status', 'max_concurrent_chats', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusEnum(): AgentPresenceStatus
    {
        return AgentPresenceStatus::tryFrom($this->status) ?? AgentPresenceStatus::Offline;
    }

    public function acceptsChats(): bool
    {
        return $this->statusEnum()->acceptsChats();
    }
}
