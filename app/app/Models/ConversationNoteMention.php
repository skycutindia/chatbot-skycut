<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationNoteMention extends Model
{
    protected $fillable = [
        'conversation_note_id',
        'conversation_id',
        'mentioned_user_id',
        'mentioned_by_user_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(ConversationNote::class, 'conversation_note_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }

    public function mentionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_by_user_id');
    }
}
