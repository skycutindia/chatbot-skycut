<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConversationRating;
use App\Models\Website;
use App\Services\AnalyticsService;
use App\Services\WidgetRateLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetEventController extends Controller
{
    public function __construct(
        protected AnalyticsService $analytics,
        protected WidgetRateLimitService $rateLimiter,
    ) {}

    public function store(Request $request): JsonResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');

        if ($this->rateLimiter->tooManyAttempts($website, $request)) {
            return $this->rateLimiter->rateLimitResponse();
        }

        $validated = $request->validate([
            'event_type' => 'required|string|max:64',
            'visitor_id' => 'nullable|string|max:64',
            'conversation_id' => 'nullable|integer',
            'payload' => 'nullable|array',
        ]);

        $this->analytics->track($website, $validated['event_type'], $request, $validated);

        if ($validated['event_type'] === 'conversation_rating' && ! empty($validated['conversation_id'])) {
            $payload = $validated['payload'] ?? [];
            $score = (int) ($payload['score'] ?? 0);
            if ($score >= 1 && $score <= 5) {
                ConversationRating::updateOrCreate(
                    ['conversation_id' => $validated['conversation_id']],
                    ['score' => $score, 'comment' => $payload['comment'] ?? null]
                );
            }
        }

        return response()->json(['ok' => true]);
    }
}
