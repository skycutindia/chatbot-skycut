<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatAutomationRule extends Model
{
    protected $fillable = [
        'organization_id', 'website_id', 'name',
        'trigger_type', 'trigger_config',
        'action_type', 'action_config',
        'priority', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'action_config' => 'array',
            'is_active' => 'boolean',
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

    public function triggerLabel(): string
    {
        return match ($this->trigger_type) {
            'keyword' => 'Message contains keyword',
            'new_conversation' => 'New conversation started',
            'inactive' => 'Conversation inactive',
            default => ucfirst(str_replace('_', ' ', $this->trigger_type)),
        };
    }

    public function actionLabel(): string
    {
        return match ($this->action_type) {
            'assign_department' => 'Assign department',
            'assign_agent' => 'Auto-assign agent',
            'add_tag' => 'Add tag',
            'set_priority' => 'Set priority',
            'close' => 'Close conversation',
            'capture_lead' => 'Save as lead',
            'request_survey' => 'Request CSAT survey',
            default => ucfirst(str_replace('_', ' ', $this->action_type)),
        };
    }
}
