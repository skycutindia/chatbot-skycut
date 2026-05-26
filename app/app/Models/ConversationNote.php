<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationNote extends Model
{
    protected $fillable = ['conversation_id', 'user_id', 'body', 'is_internal'];

    protected function casts(): array
    {
        return ['is_internal' => 'boolean'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConversationNoteMention::class);
    }
}
