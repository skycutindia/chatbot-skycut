<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebhookController extends Controller
{
    public function index(Website $website): View
    {
        $webhooks = $website->webhooks()->latest()->get();

        return view('dashboard.websites.webhooks', [
            'website' => $website,
            'webhooks' => $webhooks,
            'eventOptions' => Webhook::eventOptions(),
        ]);
    }

    public function store(Request $request, Website $website): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'url' => 'required|url|max:2048',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:lead.created,chat.started,chat.closed',
        ]);

        $website->webhooks()->create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'events' => $validated['events'],
            'secret' => 'whsec_'.Str::random(32),
            'is_active' => true,
        ]);

        return back()->with('success', 'Webhook endpoint added.');
    }

    public function destroy(Website $website, Webhook $webhook): RedirectResponse
    {
        abort_unless($webhook->website_id === $website->id, 404);
        $webhook->delete();

        return back()->with('success', 'Webhook removed.');
    }
}
