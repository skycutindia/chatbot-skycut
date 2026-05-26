<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\QaPair;
use App\Models\Website;
use App\Services\WidgetConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QaPairController extends Controller
{
    public function store(Request $request, Website $website, WidgetConfigService $config): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:5000',
            'trigger_keywords' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);

        $keywords = array_filter(array_map('trim', explode(',', $validated['trigger_keywords'] ?? '')));

        $website->qaPairs()->create([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'trigger_keywords' => $keywords ?: null,
            'priority' => $validated['priority'] ?? 0,
        ]);

        $config->invalidate($website);

        return back()->with('success', 'Q&A pair added.');
    }

    public function destroy(Website $website, QaPair $qaPair, WidgetConfigService $config): RedirectResponse
    {
        abort_unless($qaPair->website_id === $website->id, 404);
        $qaPair->delete();
        $config->invalidate($website);

        return back()->with('success', 'Q&A pair removed.');
    }

    public function import(Request $request, Website $website, WidgetConfigService $config): RedirectResponse
    {
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

            $website->qaPairs()->create([
                'question' => $question,
                'answer' => $answer,
                'trigger_keywords' => $keywords ?: null,
                'category' => $category,
                'is_active' => true,
                'is_published' => true,
            ]);
            $imported++;
        }

        fclose($handle);
        $config->invalidate($website);

        $message = "Imported {$imported} Q&A pair".($imported === 1 ? '' : 's').'.';
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} invalid row".($skipped === 1 ? '' : 's').'.';
        }

        return back()->with('success', $message);
    }
}
