<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversation_id', 'user_id', 'event_type', 'payload', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(Conversation $conversation, string $type, ?User $user = null, array $payload = []): self
    {
        return self::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user?->id,
            'event_type' => $type,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
