<?php

namespace App\Services;

use App\Models\KnowledgeArticle;
use App\Models\Website;
use Illuminate\Support\Str;

class KnowledgeIndexerService
{
    public function __construct(
        protected EmbeddingService $embeddings,
    ) {}

    public function indexArticle(Website $website, string $title, string $content, ?string $sourceUrl = null): KnowledgeArticle
    {
        $slug = Str::slug(Str::limit($title, 80, '')).'-'.Str::lower(Str::random(6));

        $article = KnowledgeArticle::updateOrCreate(
            [
                'website_id' => $website->id,
                'source_url' => $sourceUrl,
            ],
            [
                'title' => Str::limit($title, 255, ''),
                'slug' => $slug,
                'content' => $content,
                'is_published' => true,
            ]
        );

        $this->embeddings->indexArticle($article);

        return $article;
    }

    public function refreshArticle(KnowledgeArticle $article): void
    {
        $this->embeddings->indexArticle($article);
    }

    /** @param list<array{title: string, content: string}> $items */
    public function indexBatch(Website $website, array $items): int
    {
        $count = 0;
        foreach ($items as $item) {
            if (empty($item['title']) || empty($item['content'])) {
                continue;
            }
            $this->indexArticle($website, $item['title'], $item['content']);
            $count++;
        }

        return $count;
    }
}
