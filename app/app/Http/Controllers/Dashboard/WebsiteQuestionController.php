<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\AuthorizesTenantRole;
use App\Http\Controllers\Controller;
use App\Models\QaPair;
use App\Models\Website;
use App\Services\WidgetConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteQuestionController extends Controller
{
    use AuthorizesTenantRole;

    public function index(Request $request, Website $website): View
    {
        $this->ensureWebsiteInOrganization($request, $website);

        $query = $website->qaPairs()->orderByDesc('priority')->orderBy('question');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        if ($category = trim((string) $request->query('category', ''))) {
            $query->where('category', $category);
        }

        if ($request->query('status') === 'disabled') {
            $query->where('is_active', false);
        } elseif ($request->query('status') === 'enabled') {
            $query->where('is_active', true);
        }

        $questions = $query->paginate(20)->withQueryString();
        $categories = $website->qaPairs()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('dashboard.websites.questions', compact('website', 'questions', 'categories'));
    }

    public function store(Request $request, Website $website, WidgetConfigService $config): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $validated = $this->validateQuestion($request);

        $website->qaPairs()->create($validated);
        $config->invalidate($website);

        return back()->with('success', 'Question added.');
    }

    public function update(Request $request, Website $website, QaPair $qaPair, WidgetConfigService $config): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);
        abort_unless($qaPair->website_id === $website->id, 404);

        $validated = $this->validateQuestion($request);
        $qaPair->update($validated);
        $config->invalidate($website);

        return back()->with('success', 'Question updated.');
    }

    public function clone(Request $request, Website $website, QaPair $qaPair, WidgetConfigService $config): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);
        abort_unless($qaPair->website_id === $website->id, 404);

        $copy = $qaPair->replicate(['version']);
        $copy->question = $qaPair->question.' (copy)';
        $copy->save();

        $config->invalidate($website);

        return back()->with('success', 'Question cloned.');
    }

    public function destroy(Request $request, Website $website, QaPair $qaPair, WidgetConfigService $config): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);
        abort_unless($qaPair->website_id === $website->id, 404);

        $qaPair->delete();
        $config->invalidate($website);

        return back()->with('success', 'Question deleted.');
    }

    public function bulkDestroy(Request $request, Website $website, WidgetConfigService $config): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $deleted = $website->qaPairs()->whereIn('id', $validated['ids'])->delete();
        $config->invalidate($website);

        return back()->with('success', "Deleted {$deleted} question(s).");
    }

    public function import(Request $request, Website $website, WidgetConfigService $config): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        if (! $handle) {
            return back()->withErrors(['file' => 'Could not read file.']);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return back()->withErrors(['file' => 'Empty CSV file.']);
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $header);
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) < 2) {
                $skipped++;

                continue;
            }

            $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), ''));
            if ($data === false) {
                $skipped++;

                continue;
            }

            $question = trim($data['question'] ?? $data['q'] ?? $row[0] ?? '');
            $answer = trim($data['answer'] ?? $data['a'] ?? $row[1] ?? '');
            if ($question === '' || $answer === '') {
                $skipped++;

                continue;
            }

            $keywordsRaw = $data['keywords'] ?? $data['trigger_keywords'] ?? $row[2] ?? '';
            $keywords = array_filter(array_map('trim', preg_split('/[,;]+/', (string) $keywordsRaw) ?: []));
            $category = trim($data['category'] ?? $row[3] ?? '') ?: null;
            $tagsRaw = $data['tags'] ?? $row[4] ?? '';
            $tags = array_filter(array_map('trim', preg_split('/[,;]+/', (string) $tagsRaw) ?: []));
            $priority = (int) ($data['priority'] ?? $row[5] ?? 0);

            $website->qaPairs()->create([
                'question' => $question,
                'answer' => $answer,
                'trigger_keywords' => $keywords ?: null,
                'category' => $category,
                'tags' => $tags ?: null,
                'priority' => $priority,
                'is_active' => true,
                'is_published' => true,
            ]);
            $imported++;
        }

        fclose($handle);
        $config->invalidate($website);

        $message = "Imported {$imported} question(s).";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} invalid row(s).";
        }

        return back()->with('success', $message);
    }

    /** @return array<string, mixed> */
    protected function validateQuestion(Request $request): array
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:5000',
            'alternate_answers_text' => 'nullable|string|max:10000',
            'trigger_keywords' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:120',
            'tags' => 'nullable|string|max:500',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $keywords = array_filter(array_map('trim', explode(',', $validated['trigger_keywords'] ?? '')));
        $tags = array_filter(array_map('trim', explode(',', $validated['tags'] ?? '')));
        $alternates = array_values(array_filter(array_map(
            'trim',
            preg_split('/\r\n|\r|\n/', $validated['alternate_answers_text'] ?? '') ?: []
        )));

        return [
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'alternate_answers' => $alternates ?: null,
            'trigger_keywords' => $keywords ?: null,
            'category' => $validated['category'] ?? null,
            'tags' => $tags ?: null,
            'priority' => $validated['priority'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'is_published' => true,
        ];
    }
}
