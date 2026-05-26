<?php

namespace App\Console\Commands;

use App\Models\KnowledgeArticle;
use App\Models\Website;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;

class EmbedKnowledgeCommand extends Command
{
    protected $signature = 'knowledge:embed {website? : Website ID to limit scope}';

    protected $description = 'Generate OpenAI embeddings for knowledge articles (semantic search)';

    public function handle(EmbeddingService $embeddings): int
    {
        if (! $embeddings->isEnabled()) {
            $this->error('Semantic search is disabled or OpenAI is not configured.');

            return self::FAILURE;
        }

        $websiteId = $this->argument('website');

        $query = KnowledgeArticle::query()
            ->where('is_published', true)
            ->when($websiteId, fn ($q) => $q->where('website_id', $websiteId));

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No articles to embed.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunkById(50, function ($articles) use ($embeddings, $bar) {
            foreach ($articles as $article) {
                $embeddings->indexArticle($article);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Embedded {$total} article(s).");

        if ($websiteId) {
            $website = Website::find($websiteId);
            $this->line($website ? "Website: {$website->name}" : "Website ID: {$websiteId}");
        }

        return self::SUCCESS;
    }
}
