<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    protected $fillable = [
        'website_id', 'name', 'url', 'events', 'secret', 'is_active', 'last_triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public static function eventOptions(): array
    {
        return [
            'lead.created' => 'New lead',
            'chat.started' => 'New chat',
            'chat.closed' => 'Chat closed',
        ];
    }
}
