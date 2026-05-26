<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\TriggerKeyword;
use App\Models\Website;
use App\Services\WidgetConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TriggerKeywordController extends Controller
{
    public function index(Website $website): View
    {
        $keywords = $website->triggerKeywords()->orderBy('keyword')->get();

        return view('dashboard.websites.keywords', compact('website', 'keywords'));
    }

    public function store(Request $request, Website $website, WidgetConfigService $config): RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:120',
            'response' => 'required|string|max:4000',
            'action' => 'nullable|string|max:64',
        ]);

        $website->triggerKeywords()->create([
            'keyword' => $validated['keyword'],
            'response' => $validated['response'],
            'action' => $validated['action'] ?? 'reply',
            'is_active' => true,
        ]);

        $config->invalidate($website);

        return back()->with('success', 'Trigger keyword added. Widget updates instantly.');
    }

    public function destroy(Website $website, TriggerKeyword $triggerKeyword, WidgetConfigService $config): RedirectResponse
    {
        abort_unless($triggerKeyword->website_id === $website->id, 404);
        $triggerKeyword->delete();
        $config->invalidate($website);

        return back()->with('success', 'Keyword removed.');
    }
}
