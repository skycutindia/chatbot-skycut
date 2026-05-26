<?php

namespace App\Http\Middleware;

use App\Models\Website;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if ($user->roleEnum()->isPlatformLevel()) {
            return $next($request);
        }

        if (! $user->organization_id) {
            abort(403);
        }

        $website = $request->route('website');
        if ($website instanceof Website && $website->organization_id !== $user->organization_id) {
            abort(403);
        }

        return $next($request);
    }
}
