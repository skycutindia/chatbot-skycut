<?php

namespace App\Services;

use App\Models\KnowledgeArticle;
use App\Models\PlatformSetting;
use App\Models\Website;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EmbeddingService
{
    public function isEnabled(): bool
    {
        if (! config('chatbot.semantic_search.enabled')) {
            return false;
        }

        return (bool) ($this->apiKey());
    }

    public function embedText(string $text): ?array
    {
        $apiKey = $this->apiKey();

        if (! $apiKey) {
            return null;
        }

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post(app(AiConfigService::class)->resolveBaseUrl().'/embeddings', [
                'model' => config('chatbot.semantic_search.model'),
                'input' => Str::limit(trim($text), 8000),
            ]);

        if (! $response->successful()) {
            return null;
        }

        $embedding = $response->json('data.0.embedding');

        return is_array($embedding) ? $embedding : null;
    }

    public function indexArticle(KnowledgeArticle $article): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $text = trim($article->title."\n\n".strip_tags($article->content));

        if ($text === '') {
            return;
        }

        $embedding = $this->embedText($text);

        if ($embedding) {
            $article->forceFill([
                'embedding' => $embedding,
                'embedded_at' => now(),
            ])->save();
        }
    }

    public function search(Website $website, string $query): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $queryEmbedding = $this->embedText($query);

        if (! $queryEmbedding) {
            return null;
        }

        $minScore = (float) config('chatbot.semantic_search.min_score', 0.72);
        $bestArticle = null;
        $bestScore = 0.0;

        KnowledgeArticle::query()
            ->where('website_id', $website->id)
            ->where('is_published', true)
            ->whereNotNull('embedding')
            ->select(['id', 'title', 'content', 'embedding'])
            ->chunkById(100, function ($articles) use ($queryEmbedding, $minScore, &$bestArticle, &$bestScore) {
                foreach ($articles as $article) {
                    $score = $this->cosineSimilarity($queryEmbedding, $article->embedding ?? []);

                    if ($score >= $minScore && $score > $bestScore) {
                        $bestScore = $score;
                        $bestArticle = $article;
                    }
                }
            });

        if (! $bestArticle) {
            return null;
        }

        return [
            'answer' => Str::limit(strip_tags($bestArticle->content), 800),
            'score' => round($bestScore, 4),
            'article_id' => $bestArticle->id,
            'source' => 'semantic_search',
        ];
    }

    /** @param list<float> $a @param list<float> $b */
    public function cosineSimilarity(array $a, array $b): float
    {
        if ($a === [] || $b === [] || count($a) !== count($b)) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $value) {
            $other = $b[$i] ?? 0.0;
            $dot += $value * $other;
            $normA += $value * $value;
            $normB += $other * $other;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    protected function apiKey(): ?string
    {
        return app(AiConfigService::class)->resolveApiKey();
    }
}
