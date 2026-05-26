<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SuggestedQuestion;
use App\Models\Website;
use App\Services\WidgetConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SuggestedQuestionController extends Controller
{
    public function store(Request $request, Website $website, WidgetConfigService $config): RedirectResponse
    {
        $validated = $request->validate(['question' => 'required|string|max:255']);

        $website->suggestedQuestions()->create([
            'question' => $validated['question'],
            'sort_order' => $website->suggestedQuestions()->count(),
        ]);

        $config->invalidate($website);

        return back()->with('success', 'Suggested question added.');
    }

    public function destroy(Website $website, SuggestedQuestion $question, WidgetConfigService $config): RedirectResponse
    {
        abort_unless($question->website_id === $website->id, 404);
        $question->delete();
        $config->invalidate($website);

        return back()->with('success', 'Question removed.');
    }
}
