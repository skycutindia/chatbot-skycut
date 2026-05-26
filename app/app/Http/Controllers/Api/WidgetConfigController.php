<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Services\AnalyticsService;
use App\Services\WidgetConfigService;
use App\Services\WidgetRateLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetConfigController extends Controller
{
    public function __construct(
        protected WidgetConfigService $configService,
        protected AnalyticsService $analytics,
        protected WidgetRateLimitService $rateLimiter,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');

        if ($this->rateLimiter->tooManyAttempts($website, $request)) {
            return $this->rateLimiter->rateLimitResponse();
        }

        $this->analytics->track($website, 'widget_config_loaded', $request, [
            'visitor_id' => $request->query('visitor_id'),
        ]);

        return response()
            ->json($this->configService->getPublicConfig($website))
            ->header('Cache-Control', 'public, max-age='.config('chatbot.widget.cache_ttl', 60));
    }
}
