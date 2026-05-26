<?php

namespace App\Models;

use App\Enums\ConversationPriority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'website_id', 'visitor_id', 'visitor_name', 'visitor_email', 'visitor_phone', 'visitor_company', 'visitor_job_title',
        'status', 'mode', 'channel', 'channel_contact_id', 'priority', 'tags', 'sla_due_at',
        'is_starred', 'is_pinned', 'snoozed_until', 'agent_unread_count', 'visit_count', 'low_confidence_streak',
        'assigned_user_id', 'department_id', 'page_url', 'source_url', 'metadata', 'agent_draft',
        'ip_address', 'user_agent', 'utm_params',
        'last_message_at', 'first_response_at', 'closed_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'tags' => 'array',
            'utm_params' => 'array',
            'is_starred' => 'boolean',
            'is_pinned' => 'boolean',
            'last_message_at' => 'datetime',
            'first_response_at' => 'datetime',
            'closed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'snoozed_until' => 'datetime',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(ConversationNote::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ChatEvent::class)->orderByDesc('created_at');
    }

    public function lead(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Lead::class);
    }

    public function rating(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ConversationRating::class);
    }

    public function priorityEnum(): ConversationPriority
    {
        return ConversationPriority::tryFrom($this->priority) ?? ConversationPriority::Normal;
    }

    public function isAwaitingAgent(): bool
    {
        return $this->mode === 'human' || $this->status === 'awaiting_agent';
    }

    public function isWhatsApp(): bool
    {
        return $this->channel === 'whatsapp';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'awaiting_agent', 'pending', 'waiting_visitor'], true);
    }

    public function isSnoozed(): bool
    {
        return $this->snoozed_until && $this->snoozed_until->isFuture();
    }

    public function scopeVisibleInInbox($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now());
        });
    }
}
