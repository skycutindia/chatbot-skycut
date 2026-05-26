<?php

namespace App\Http\Middleware;

use App\Models\Website;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateWidgetDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');
        $config = $website->configuration;

        if (! $config->require_domain_validation) {
            return $next($request);
        }

        if (app()->environment('local') && config('app.debug')) {
            return $next($request);
        }

        $origin = $request->header('Origin') ?? $request->header('Referer');
        if (! $origin) {
            return $next($request);
        }

        $host = parse_url($origin, PHP_URL_HOST);
        if (! $host) {
            return response()->json(['error' => 'Invalid origin.'], 403);
        }

        $allowed = $website->allowedDomains->pluck('domain')->map(fn ($d) => strtolower($d));
        if ($allowed->isEmpty()) {
            return $next($request);
        }

        $host = strtolower($host);
        $valid = $allowed->contains(fn ($domain) => $host === $domain || str_ends_with($host, '.'.$domain));

        if (! $valid) {
            return response()->json(['error' => 'Domain not allowed for this chatbot.'], 403);
        }

        return $next($request);
    }
}
