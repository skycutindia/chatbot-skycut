<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatbotHubController extends Controller
{
    /** @deprecated Hub merged into websites index — keep route for bookmarks */
    public function show(Request $request, Website $website): RedirectResponse
    {
        abort_unless($website->organization_id === $request->user()->organization_id
            || $request->user()->roleEnum()->isPlatformLevel(), 403);

        return redirect()->route('websites.index', ['manage' => $website->id]);
    }
}
