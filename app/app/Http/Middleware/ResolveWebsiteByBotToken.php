<?php

namespace App\Http\Middleware;

use App\Models\Website;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveWebsiteByBotToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('botToken') ?? $request->header('X-Bot-Token');

        $website = Website::query()
            ->where('bot_token', $token)
            ->where('is_active', true)
            ->with('configuration', 'allowedDomains')
            ->first();

        if (! $website) {
            return response()->json(['error' => 'Invalid or inactive bot token.'], 404);
        }

        $request->attributes->set('website', $website);

        return $next($request);
    }
}
