<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'conversation_id', 'channel', 'event_type',
        'status', 'payload', 'error_message', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public static function record(
        int $organizationId,
        string $channel,
        string $eventType,
        string $status,
        ?int $conversationId = null,
        ?array $payload = null,
        ?string $error = null,
    ): self {
        return self::create([
            'organization_id' => $organizationId,
            'conversation_id' => $conversationId,
            'channel' => $channel,
            'event_type' => $eventType,
            'status' => $status,
            'payload' => $payload,
            'error_message' => $error,
            'created_at' => now(),
        ]);
    }
}
