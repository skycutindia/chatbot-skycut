<?php

namespace App\Services;

use App\Models\Website;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDispatchService
{
    public function dispatch(Website $website, string $event, array $payload = []): void
    {
        $hooks = $website->webhooks()->where('is_active', true)->get();

        foreach ($hooks as $hook) {
            if (! in_array($event, $hook->events ?? [], true)) {
                continue;
            }

            try {
                Http::timeout(8)->post($hook->url, [
                    'event' => $event,
                    'website_id' => $website->id,
                    'bot_token' => $website->bot_token,
                    'payload' => $payload,
                    'timestamp' => now()->toIso8601String(),
                ], [
                    'headers' => [
                        'X-Webhook-Secret' => $hook->secret ?? '',
                        'User-Agent' => 'AI-Chatbot-Hub-Pro/1.0',
                    ],
                ]);

                $hook->update(['last_triggered_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('Webhook dispatch failed', [
                    'webhook_id' => $hook->id,
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
