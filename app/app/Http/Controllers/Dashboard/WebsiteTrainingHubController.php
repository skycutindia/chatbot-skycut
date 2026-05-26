<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\AuthorizesTenantRole;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessKnowledgeSourceJob;
use App\Models\TriggerKeyword;
use App\Models\Website;
use App\Services\UrlQaGeneratorService;
use App\Services\WidgetConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteTrainingHubController extends Controller
{
    use AuthorizesTenantRole;

    private function draftSessionKey(Website $website): string
    {
        return 'training_qa_draft_'.$website->id;
    }

    public function index(Request $request, Website $website): View|RedirectResponse
    {
        $this->ensureWebsiteInOrganization($request, $website);

        if ($request->filled('tab') && $request->query('tab') !== 'ai') {
            return redirect()->route('websites.training.index', $website);
        }

        $qaCount = $website->qaPairs()->count();
        $draft = $request->session()->get($this->draftSessionKey($website));

        return view('dashboard.websites.training-hub', compact(
            'website',
            'qaCount',
            'draft',
        ));
    }

    public function generateQaFromUrl(
        Request $request,
        Website $website,
        UrlQaGeneratorService $generator,
    ): RedirectResponse {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'max_pairs' => 'nullable|integer|min:3|max:15',
        ]);

        try {
            $result = $generator->generateFromUrl(
                $website,
                $validated['url'],
                (int) ($validated['max_pairs'] ?? 8),
            );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage())
                ->withFragment('ai');
        }

        $request->session()->put($this->draftSessionKey($website), [
            'url' => $result['url'],
            'title' => $result['title'],
            'pairs' => $result['pairs'],
            'generated_at' => now()->toIso8601String(),
        ]);

        return redirect()
            ->route('websites.training.index', $website)
            ->with('success', count($result['pairs']).' Q&A suggestions ready for review.');
    }

    public function approveQaDraft(
        Request $request,
        Website $website,
        WidgetConfigService $config,
    ): RedirectResponse {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $draft = $request->session()->get($this->draftSessionKey($website));
        if (! $draft || empty($draft['pairs'])) {
            return back()->with('error', 'No draft Q&A to approve. Generate from a URL first.');
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.approve' => 'nullable|boolean',
            'items.*.question' => 'required|string|max:500',
            'items.*.answer' => 'required|string|max:5000',
            'items.*.trigger_keywords' => 'nullable|string|max:500',
            'items.*.category' => 'nullable|string|max:120',
        ]);

        $saved = 0;
        foreach ($validated['items'] as $index => $item) {
            if (! $request->boolean("items.{$index}.approve")) {
                continue;
            }

            $keywords = array_filter(array_map('trim', explode(',', $item['trigger_keywords'] ?? '')));

            $website->qaPairs()->create([
                'question' => $item['question'],
                'answer' => $item['answer'],
                'trigger_keywords' => $keywords ?: null,
                'category' => $item['category'] ?? 'From URL',
                'priority' => 5,
                'is_active' => true,
                'is_published' => true,
            ]);
            $saved++;
        }

        if ($saved === 0) {
            return back()->with('error', 'Select at least one Q&A to save.');
        }

        $request->session()->forget($this->draftSessionKey($website));
        $config->invalidate($website);

        return redirect()
            ->route('websites.questions.index', $website)
            ->with('success', "{$saved} Q&A pair".($saved === 1 ? '' : 's').' saved to your library.');
    }

    public function discardQaDraft(Request $request, Website $website): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $request->session()->forget($this->draftSessionKey($website));

        return redirect()
            ->route('websites.training.index', $website)
            ->with('success', 'Draft discarded.');
    }

    public function crawl(Request $request, Website $website): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'label' => 'nullable|string|max:120',
        ]);

        $source = $website->knowledgeSources()->create([
            'type' => 'crawl',
            'label' => $validated['label'] ?? 'Website crawl',
            'source_url' => $validated['url'],
            'status' => 'pending',
        ]);

        ProcessKnowledgeSourceJob::dispatch($source);

        return redirect()
            ->route('websites.training.index', $website)
            ->with('success', 'Crawl queued. Pages will be indexed shortly.');
    }

    public function storeKeyword(Request $request, Website $website, WidgetConfigService $config): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $validated = $request->validate([
            'keyword' => 'required|string|max:120',
            'response' => 'required|string|max:5000',
        ]);

        $website->triggerKeywords()->create($validated);
        $config->invalidate($website);

        return redirect()
            ->route('websites.keywords.index', $website)
            ->with('success', 'Keyword saved.');
    }

    public function destroyKeyword(
        Request $request,
        Website $website,
        TriggerKeyword $triggerKeyword,
        WidgetConfigService $config,
    ): RedirectResponse {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);
        abort_unless($triggerKeyword->website_id === $website->id, 404);

        $triggerKeyword->delete();
        $config->invalidate($website);

        return redirect()
            ->route('websites.keywords.index', $website)
            ->with('success', 'Keyword removed.');
    }
}
