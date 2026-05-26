<?php

namespace App\Services;

use App\Models\OperatingHour;
use App\Models\Website;
use Illuminate\Support\Facades\Cache;

class WidgetConfigService
{
    public function getPublicConfig(Website $website): array
    {
        $cacheKey = "widget.config.{$website->bot_token}";
        $ttl = config('chatbot.widget.cache_ttl', 60);

        return Cache::remember($cacheKey, $ttl, fn () => $this->buildConfig($website));
    }

    public function invalidate(Website $website): void
    {
        Cache::forget("widget.config.{$website->bot_token}");
        if ($website->configuration) {
            $website->configuration->increment('config_version');
        }
        $website->touch();
    }

    public function buildConfig(Website $website): array
    {
        $website->load([
            'configuration',
            'suggestedQuestions' => fn ($q) => $q->where('is_active', true),
            'quickActions' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            'operatingHours',
            'allowedDomains',
            'translations',
            'qaPairs' => fn ($q) => $q->where('is_active', true)->orderByDesc('priority'),
            'triggerKeywords' => fn ($q) => $q->where('is_active', true),
            'escalationRules' => fn ($q) => $q->where('is_active', true)->orderByDesc('priority'),
            'knowledgeCategories' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $config = $website->configuration;
        $security = $config->security_settings ?? [];
        $withinHours = OperatingHour::isWithinHours($website);
        $translations = $website->translations
            ->groupBy('locale')
            ->map(fn ($items) => $items->pluck('value', 'key'));

        return [
            'bot_token' => $website->bot_token,
            'website' => [
                'id' => $website->id,
                'name' => $website->name,
            ],
            'appearance' => [
                'bot_name' => $config->bot_name,
                'avatar_url' => $config->avatar_url,
                'logo_url' => $website->logo_url,
                'brand_colors' => $website->brand_colors,
                'primary_color' => $config->primary_color,
                'secondary_color' => $config->secondary_color,
                'theme_mode' => $config->theme_mode,
                'position' => $config->position,
                'offset_bottom' => max(0, min(200, (int) ($security['widget_offset_bottom'] ?? 24))),
                'offset_side' => max(0, min(200, (int) ($security['widget_offset_side'] ?? 24))),
                'trigger_delay_seconds' => $config->trigger_delay_seconds ?? 3,
                'auto_open' => (bool) ($config->auto_open ?? false),
                'sound_enabled' => (bool) ($config->sound_enabled ?? true),
                'typing_animation' => (bool) ($config->typing_animation ?? true),
                'custom_css' => $config->custom_css,
                'custom_js' => $config->custom_js,
            ],
            'quick_actions' => $website->quickActions->map(fn ($a) => [
                'id' => $a->id,
                'label' => $a->label,
                'description' => $a->description,
                'icon' => $a->icon,
                'color' => $a->color,
                'action_type' => $a->action_type,
                'action_value' => $a->action_value,
                'custom_answer' => $a->custom_answer,
            ])->values(),
            'messages' => [
                'welcome' => $config->welcome_message,
                'typing_indicator' => $config->typing_indicator_text,
                'offline' => $config->offline_message,
                'outside_hours' => $config->outside_hours_message,
                'fallback' => $config->fallback_message,
            ],
            'channels' => $config->widgetChannels(),
            'suggested_questions' => $website->suggestedQuestions->pluck('question')->values(),
            'operating_hours' => [
                'is_open' => $withinHours && $website->is_active,
                'schedule' => $website->operatingHours->map(fn ($h) => [
                    'day' => $h->day_of_week,
                    'opens_at' => $h->opens_at,
                    'closes_at' => $h->closes_at,
                    'is_closed' => $h->is_closed,
                    'timezone' => $h->timezone,
                ])->values(),
            ],
            'ai' => [
                'enabled' => $config->ai_enabled && $website->is_active,
                'model' => $config->ai_model,
                'temperature' => (float) $config->ai_temperature,
                'confidence_threshold' => (float) $config->confidence_threshold,
            ],
            'modules' => $config->modules(),
            'reaction_emojis' => config('chatbot.reactions.emojis', ['👍', '❤️', '😂', '😮', '🙏']),
            'locale' => $config->locale,
            'translations' => $translations,
            'qa_pairs' => $website->qaPairs->map(fn ($q) => [
                'question' => $q->question,
                'answer' => $q->answer,
                'keywords' => $q->trigger_keywords ?? [],
            ])->values(),
            'trigger_keywords' => $website->triggerKeywords->map(fn ($t) => [
                'keyword' => $t->keyword,
                'action' => $t->action,
                'response' => $t->response,
            ])->values(),
            'escalation_rules' => $website->escalationRules->map(fn ($r) => [
                'name' => $r->name,
                'trigger_type' => $r->trigger_type,
                'trigger_config' => $r->trigger_config,
                'action' => $r->action,
                'action_config' => $r->action_config,
            ])->values(),
            'knowledge' => [
                'categories' => $website->knowledgeCategories->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                ])->values(),
            ],
            'security' => [
                'require_domain_validation' => $config->require_domain_validation,
            ],
            'api' => [
                'chat' => url("/api/widget/{$website->bot_token}/chat"),
                'events' => url("/api/widget/{$website->bot_token}/events"),
            ],
            'version' => (int) ($config->config_version ?? $website->updated_at?->timestamp ?? time()),
        ];
    }
}
