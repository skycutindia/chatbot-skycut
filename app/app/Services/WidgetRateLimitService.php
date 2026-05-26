<?php

namespace App\Services;

use App\Models\Website;
use App\Models\WidgetRateLimit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetRateLimitService
{
    public function rateLimitResponse(): JsonResponse
    {
        return response()->json([
            'error' => 'rate_limit_exceeded',
            'message' => 'You\'re sending messages too quickly. Please wait a moment and try again.',
        ], 429);
    }

    public function tooManyAttempts(Website $website, Request $request): bool
    {
        $config = $website->configuration;
        $identifier = $request->ip() ?? 'unknown';
        $token = $website->bot_token;

        if ($this->exceeded($token, $identifier, 'minute', $config->rate_limit_per_minute, 60)) {
            return true;
        }

        return $this->exceeded($token, $identifier, 'hour', $config->rate_limit_per_hour, 3600);
    }

    protected function exceeded(string $token, string $identifier, string $window, int $max, int $seconds): bool
    {
        $record = WidgetRateLimit::query()->firstOrCreate(
            [
                'bot_token' => $token,
                'identifier' => $identifier,
                'window' => $window,
            ],
            ['hits' => 0, 'expires_at' => now()->addSeconds($seconds)]
        );

        if ($record->expires_at->isPast()) {
            $record->update(['hits' => 0, 'expires_at' => now()->addSeconds($seconds)]);
        }

        $record->increment('hits');

        return $record->fresh()->hits > $max;
    }
}
