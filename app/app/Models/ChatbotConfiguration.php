<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotConfiguration extends Model
{
    protected $fillable = [
        'website_id', 'bot_name', 'avatar_url', 'primary_color', 'secondary_color',
        'theme_mode', 'position', 'locale', 'welcome_message', 'typing_indicator_text',
        'offline_message', 'outside_hours_message', 'ai_provider', 'ai_model',
        'ai_temperature', 'max_tokens', 'confidence_threshold', 'handoff_triggers',
        'system_prompt', 'ai_enabled',
        'trigger_delay_seconds', 'auto_open', 'auto_open_rules', 'sound_enabled', 'typing_animation', 'bot_online',
        'custom_css', 'custom_js', 'enabled_modules', 'rate_limit_per_minute',
        'rate_limit_per_hour', 'require_domain_validation', 'security_settings',
        'bot_description', 'fallback_message', 'ai_tone', 'widget_channels', 'config_version',
    ];

    protected function casts(): array
    {
        return [
            'ai_temperature' => 'float',
            'confidence_threshold' => 'float',
            'ai_enabled' => 'boolean',
            'enabled_modules' => 'array',
            'handoff_triggers' => 'array',
            'auto_open_rules' => 'array',
            'auto_open' => 'boolean',
            'sound_enabled' => 'boolean',
            'typing_animation' => 'boolean',
            'bot_online' => 'boolean',
            'require_domain_validation' => 'boolean',
            'security_settings' => 'array',
            'widget_channels' => 'array',
            'config_version' => 'integer',
        ];
    }

    public function defaultWidgetChannels(): array
    {
        return [
            'whatsapp' => [
                'enabled' => false,
                'number' => '',
                'message' => 'Hi, I need help from your website chatbot.',
            ],
            'email' => [
                'enabled' => false,
                'address' => '',
                'subject' => 'Support request from chatbot',
            ],
        ];
    }

    public function widgetChannels(): array
    {
        return array_merge($this->defaultWidgetChannels(), $this->widget_channels ?? []);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function defaultModules(): array
    {
        return [
            'chat' => true,
            'suggested_questions' => true,
            'knowledge_search' => true,
            'live_agent' => true,
            'pre_chat_form' => true,
            'csat_survey' => true,
            'analytics' => true,
            'typing_indicator' => true,
            'widget_draggable' => false,
            'widget_fullscreen' => false,
        ];
    }

    public function modules(): array
    {
        return array_merge($this->defaultModules(), $this->enabled_modules ?? []);
    }
}
