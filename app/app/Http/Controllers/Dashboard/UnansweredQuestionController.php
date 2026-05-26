<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\UnansweredQuestion;
use App\Models\Website;
use App\Services\UnansweredQuestionService;
use App\Services\WidgetConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UnansweredQuestionController extends Controller
{
    public function index(Request $request, Website $website): View
    {
        $questions = UnansweredQuestion::query()
            ->where('website_id', $website->id)
            ->with('conversation')
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        return view('dashboard.websites.unanswered', compact('website', 'questions'));
    }

    public function resolve(Request $request, Website $website, UnansweredQuestion $unanswered, UnansweredQuestionService $service): RedirectResponse
    {
        abort_unless($unanswered->website_id === $website->id, 404);

        $validated = $request->validate([
            'answer' => 'required|string|max:5000',
            'category' => 'nullable|string|max:120',
        ]);

        $service->promoteToQa($unanswered, $validated['answer'], $validated['category'] ?? null);

        return back()->with('success', 'Answer saved and added to Q&A. Live widget updated.');
    }

    public function dismiss(Website $website, UnansweredQuestion $unanswered): RedirectResponse
    {
        abort_unless($unanswered->website_id === $website->id, 404);
        $unanswered->update(['status' => 'dismissed', 'resolved_at' => now()]);

        return back()->with('success', 'Question dismissed.');
    }

    public function bulk(Request $request, Website $website, UnansweredQuestionService $service): RedirectResponse
    {
        abort_unless($website->organization_id === $request->user()->organization_id, 403);

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'action' => 'required|in:dismiss,promote',
            'answer' => 'nullable|string|max:5000',
            'answers' => 'nullable|array',
            'answers.*' => 'nullable|string|max:5000',
            'category' => 'nullable|string|max:120',
        ]);

        $questions = UnansweredQuestion::query()
            ->where('website_id', $website->id)
            ->where('status', 'open')
            ->whereIn('id', $validated['ids'])
            ->get();

        if ($questions->isEmpty()) {
            return back()->with('error', 'No open questions matched your selection.');
        }

        if ($validated['action'] === 'promote') {
            $perRow = $validated['answers'] ?? [];
            $shared = trim((string) ($validated['answer'] ?? ''));
            $missing = $questions->filter(function ($question) use ($perRow, $shared) {
                $rowAnswer = trim((string) ($perRow[$question->id] ?? $perRow[(string) $question->id] ?? ''));

                return $rowAnswer === '' && $shared === '';
            });

            if ($missing->isNotEmpty()) {
                return back()
                    ->withInput()
                    ->with('error', 'Provide an answer for each selected question, or one shared answer for all.');
            }
        }

        DB::transaction(function () use ($validated, $questions, $service) {
            $sharedAnswer = trim((string) ($validated['answer'] ?? ''));
            $perRow = $validated['answers'] ?? [];

            foreach ($questions as $question) {
                if ($validated['action'] === 'dismiss') {
                    $question->update(['status' => 'dismissed', 'resolved_at' => now()]);

                    continue;
                }

                $answer = trim((string) ($perRow[$question->id] ?? $perRow[(string) $question->id] ?? ''));
                if ($answer === '') {
                    $answer = $sharedAnswer;
                }

                $service->promoteToQa($question, $answer, $validated['category'] ?? null);
            }
        });

        $count = $questions->count();
        $label = $validated['action'] === 'dismiss' ? 'dismissed' : 'added to Q&A';

        return back()->with('success', "{$count} question(s) {$label}.");
    }
}
