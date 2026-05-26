<?php

namespace App\Jobs;

use App\Models\KnowledgeSource;
use App\Models\Website;
use App\Services\KnowledgeFileParserService;
use App\Services\KnowledgeIndexerService;
use App\Services\WebsiteCrawlerService;
use App\Services\WidgetConfigService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessKnowledgeSourceJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public KnowledgeSource $source,
    ) {}

    public function handle(
        WebsiteCrawlerService $crawler,
        KnowledgeFileParserService $parser,
        KnowledgeIndexerService $indexer,
        WidgetConfigService $widgetConfig,
    ): void {
        $source = $this->source->fresh();
        if (! $source || $source->status === 'completed') {
            return;
        }

        $website = Website::find($source->website_id);
        if (! $website) {
            $source->markFailed('Website not found.');

            return;
        }

        $source->markProcessing();

        try {
            $count = match ($source->type) {
                'crawl' => $crawler->crawl($source, $website),
                'file' => $this->processFile($source, $website, $parser, $indexer),
                default => throw new \RuntimeException('Unknown source type.'),
            };

            $source->markCompleted($count);
            $widgetConfig->invalidate($website);
        } catch (\Throwable $e) {
            $source->markFailed($e->getMessage());
        }
    }

    protected function processFile(
        KnowledgeSource $source,
        Website $website,
        KnowledgeFileParserService $parser,
        KnowledgeIndexerService $indexer,
    ): int {
        if (! $source->file_path || ! Storage::disk('local')->exists($source->file_path)) {
            throw new \RuntimeException('Uploaded file not found.');
        }

        $path = Storage::disk('local')->path($source->file_path);
        $extension = pathinfo($source->file_name ?? '', PATHINFO_EXTENSION);
        $items = $parser->parse($path, $extension);

        return $indexer->indexBatch($website, $items);
    }
}
