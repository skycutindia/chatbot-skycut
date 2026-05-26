<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickAction extends Model
{
    protected $fillable = [
        'website_id', 'label', 'description', 'icon', 'color',
        'action_type', 'action_value', 'custom_answer',
        'display_rules', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'display_rules' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public const ACTION_TYPES = [
        'answer' => 'Custom answer',
        'message' => 'Send chat message',
        'url' => 'Open URL',
        'whatsapp' => 'WhatsApp chat',
        'email' => 'Email',
        'phone' => 'Phone call',
        'internal' => 'Internal',
    ];

    public function getInitialAttribute(): string
    {
        $label = trim((string) $this->label);
        if ($label === '') {
            return '?';
        }
        $first = mb_substr($label, 0, 1);

        return mb_strtoupper($first);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
