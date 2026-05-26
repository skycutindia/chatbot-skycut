<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentQuickReply extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'title',
        'body',
        'sort_order',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function displayLabel(): string
    {
        if ($this->title) {
            return $this->title;
        }

        return \Illuminate\Support\Str::limit($this->body, 40);
    }
}
