<?php

namespace App\Http\Middleware;

use App\Support\DemoWebsiteRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticatedDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return DemoWebsiteRedirect::redirect();
        }

        if (! $request->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return DemoWebsiteRedirect::redirect();
        }

        return $next($request);
    }
}
