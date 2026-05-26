<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessKnowledgeSourceJob;
use App\Models\KnowledgeSource;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KnowledgeTrainingController extends Controller
{
    public function index(Website $website): RedirectResponse
    {
        return redirect()->route('websites.training.index', $website);
    }

    public function crawl(Request $request, Website $website): RedirectResponse
    {
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

        return back()->with('success', 'Crawl queued. Pages will be indexed shortly.');
    }

    public function upload(Request $request, Website $website): RedirectResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:txt,csv,docx,pdf|max:15360',
            'label' => 'nullable|string|max:120',
        ]);

        $file = $request->file('file');
        $path = $file->store('knowledge/'.$website->id, 'local');

        $source = $website->knowledgeSources()->create([
            'type' => 'file',
            'label' => $validated['label'] ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'status' => 'pending',
        ]);

        ProcessKnowledgeSourceJob::dispatch($source);

        return back()->with('success', 'File queued for indexing.');
    }

    public function destroy(Website $website, KnowledgeSource $source): RedirectResponse
    {
        abort_unless($source->website_id === $website->id, 404);
        $source->delete();

        return back()->with('success', 'Training source removed.');
    }
}
