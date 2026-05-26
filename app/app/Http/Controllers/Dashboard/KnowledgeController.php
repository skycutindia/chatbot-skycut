<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Models\QaPair;
use App\Models\Website;
use App\Services\KnowledgeIndexerService;
use App\Services\WidgetConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KnowledgeController extends Controller
{
    public function index(Request $request, Website $website): View|RedirectResponse
    {
        if (! $request->filled('q')) {
            return redirect()->route('websites.training.index', $website);
        }

        $search = trim((string) $request->get('q', ''));

        $articlesQuery = $website->knowledgeArticles()->with('category')->latest();

        if ($search !== '') {
            $articlesQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $articles = $articlesQuery->paginate(20)->withQueryString();
        $categories = $website->knowledgeCategories()->orderBy('sort_order')->get();

        return view('dashboard.knowledge.index', compact('website', 'articles', 'categories', 'search'));
    }

    public function storeArticle(Request $request, Website $website, WidgetConfigService $config, KnowledgeIndexerService $indexer): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'knowledge_category_id' => 'nullable|exists:knowledge_categories,id',
            'is_published' => 'boolean',
        ]);

        $article = $indexer->indexArticle($website, $validated['title'], $validated['content']);

        if ($validated['knowledge_category_id'] ?? null) {
            $article->update(['knowledge_category_id' => $validated['knowledge_category_id']]);
        }

        if (! $request->boolean('is_active', true)) {
            $article->update(['is_published' => false]);
        }

        $config->invalidate($website);

        return back()->with('success', 'Article added.');
    }

    public function storeCategory(Request $request, Website $website): RedirectResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:120']);

        $website->knowledgeCategories()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return back()->with('success', 'Category created.');
    }

    public function updateArticle(Request $request, Website $website, KnowledgeArticle $article, WidgetConfigService $config, KnowledgeIndexerService $indexer): RedirectResponse
    {
        abort_unless($article->website_id === $website->id, 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'knowledge_category_id' => 'nullable|exists:knowledge_categories,id',
            'is_published' => 'boolean',
        ]);

        $article->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'knowledge_category_id' => $validated['knowledge_category_id'] ?? null,
            'is_published' => $request->boolean('is_published', true),
        ]);

        $indexer->refreshArticle($article->fresh());
        $config->invalidate($website);

        return back()->with('success', 'Article updated.');
    }

    public function destroyArticle(Website $website, KnowledgeArticle $article, WidgetConfigService $config): RedirectResponse
    {
        abort_unless($article->website_id === $website->id, 404);
        $article->delete();
        $config->invalidate($website);

        return back()->with('success', 'Article deleted.');
    }

    public function importFaq(Request $request, Website $website, WidgetConfigService $config, KnowledgeIndexerService $indexer): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'import_type' => 'required|in:qa,articles',
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

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }
            $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), ''));
            if ($data === false) {
                continue;
            }

            if ($request->input('import_type') === 'qa') {
                $question = $data['question'] ?? $data['q'] ?? $row[0] ?? null;
                $answer = $data['answer'] ?? $data['a'] ?? $row[1] ?? null;
                if ($question && $answer) {
                    QaPair::create([
                        'website_id' => $website->id,
                        'question' => $question,
                        'answer' => $answer,
                        'is_active' => true,
                        'is_published' => true,
                    ]);
                    $imported++;
                }
            } else {
                $title = $data['title'] ?? $row[0] ?? null;
                $content = $data['content'] ?? $data['body'] ?? $row[1] ?? null;
                if ($title && $content) {
                    $indexer->indexArticle($website, $title, $content);
                    $imported++;
                }
            }
        }

        fclose($handle);
        $config->invalidate($website);

        return back()->with('success', "Imported {$imported} records.");
    }
}
