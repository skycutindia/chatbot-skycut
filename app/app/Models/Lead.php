<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'organization_id', 'website_id', 'conversation_id',
        'name', 'email', 'phone', 'company', 'website_url',
        'status', 'assigned_user_id', 'source_url', 'ip_address',
        'device_info', 'utm_params', 'chat_transcript', 'follow_up_at',
    ];

    protected function casts(): array
    {
        return [
            'device_info' => 'array',
            'utm_params' => 'array',
            'follow_up_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    public function statusEnum(): LeadStatus
    {
        return LeadStatus::tryFrom($this->status) ?? LeadStatus::New;
    }
}
