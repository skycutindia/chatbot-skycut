<?php

namespace App\Services;

use App\Models\KnowledgeSource;
use App\Models\Website;
use DOMDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebsiteCrawlerService
{
    public function __construct(
        protected KnowledgeIndexerService $indexer,
    ) {}

    /**
     * Fetch readable text from a single URL (for AI Q&A generation).
     *
     * @return array{url: string, title: string, text: string}
     */
    public function fetchSinglePage(string $url): array
    {
        $normalized = $this->normalizeUrl($url);
        $timeout = (int) config('chatbot.crawl.timeout', 15);

        $response = Http::timeout($timeout)
            ->withHeaders(['User-Agent' => 'AIChatbotHubBot/1.0'])
            ->get($normalized);

        if (! $response->successful()) {
            throw new \RuntimeException('Could not fetch URL (HTTP '.$response->status().').');
        }

        $contentType = $response->header('Content-Type', '');
        if (! str_contains($contentType, 'text/html') && ! str_contains($contentType, 'application/xhtml')) {
            throw new \RuntimeException('URL must return an HTML page.');
        }

        $html = $response->body();
        $text = $this->extractText($html);

        if (strlen($text) < 80) {
            throw new \RuntimeException('Not enough readable content on this page.');
        }

        return [
            'url' => $normalized,
            'title' => $this->extractTitle($html) ?: $normalized,
            'text' => Str::limit($text, 14000, ''),
        ];
    }

    public function crawl(KnowledgeSource $source, Website $website): int
    {
        $startUrl = $source->source_url ?: $website->url;
        if (! $startUrl) {
            throw new \RuntimeException('No URL configured for crawl.');
        }

        $maxPages = (int) config('chatbot.crawl.max_pages', 30);
        $timeout = (int) config('chatbot.crawl.timeout', 15);
        $parsed = parse_url($startUrl);
        $host = $parsed['host'] ?? null;

        if (! $host) {
            throw new \RuntimeException('Invalid crawl URL.');
        }

        $visited = [];
        $queue = [[rtrim($startUrl, '/'), 0]];
        $indexed = 0;

        while ($queue && count($visited) < $maxPages) {
            [$url, $depth] = array_shift($queue);
            $normalized = $this->normalizeUrl($url);

            if (isset($visited[$normalized]) || $depth > (int) config('chatbot.crawl.max_depth', 2)) {
                continue;
            }

            $visited[$normalized] = true;

            try {
                $response = Http::timeout($timeout)
                    ->withHeaders(['User-Agent' => 'AIChatbotHubBot/1.0'])
                    ->get($normalized);

                if (! $response->successful()) {
                    continue;
                }

                $contentType = $response->header('Content-Type', '');
                if (! str_contains($contentType, 'text/html') && ! str_contains($contentType, 'application/xhtml')) {
                    continue;
                }

                $html = $response->body();
                $title = $this->extractTitle($html) ?: $normalized;
                $text = $this->extractText($html);

                if (strlen($text) >= 80) {
                    $this->indexer->indexArticle($website, $title, $text, $normalized);
                    $indexed++;
                }

                foreach ($this->extractLinks($html, $normalized, $host) as $link) {
                    if (! isset($visited[$this->normalizeUrl($link)])) {
                        $queue[] = [$link, $depth + 1];
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $indexed;
    }

    protected function normalizeUrl(string $url): string
    {
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $path = $path !== '/' ? rtrim($path, '/') : '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return "{$scheme}://{$host}{$port}{$path}{$query}";
    }

    protected function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            return trim(html_entity_decode(strip_tags($m[1])));
        }

        return null;
    }

    protected function extractText(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->loadHTML($html);
        libxml_clear_errors();

        foreach (['script', 'style', 'nav', 'footer', 'header', 'noscript'] as $tag) {
            while (($nodes = $dom->getElementsByTagName($tag))->length > 0) {
                $nodes->item(0)?->parentNode?->removeChild($nodes->item(0));
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $text = $body ? $body->textContent : strip_tags($html);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace("/\n{2,}/", "\n\n", trim($text));

        return $text ?? '';
    }

    /** @return list<string> */
    protected function extractLinks(string $html, string $baseUrl, string $host): array
    {
        $links = [];
        if (! preg_match_all('/href=["\']([^"\']+)["\']/i', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $href) {
            if (str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }

            $absolute = $this->resolveUrl($baseUrl, $href);
            $parsed = parse_url($absolute);
            if (($parsed['host'] ?? null) !== $host) {
                continue;
            }
            if (preg_match('/\.(pdf|jpg|jpeg|png|gif|zip|css|js)$/i', $parsed['path'] ?? '')) {
                continue;
            }

            $links[] = $this->normalizeUrl($absolute);
        }

        return array_unique($links);
    }

    protected function resolveUrl(string $base, string $relative): string
    {
        if (str_starts_with($relative, 'http://') || str_starts_with($relative, 'https://')) {
            return $relative;
        }

        $baseParts = parse_url($base);
        $origin = ($baseParts['scheme'] ?? 'https').'://'.($baseParts['host'] ?? '');

        if (str_starts_with($relative, '//')) {
            return ($baseParts['scheme'] ?? 'https').':'.$relative;
        }

        if (str_starts_with($relative, '/')) {
            return $origin.$relative;
        }

        $basePath = $baseParts['path'] ?? '/';
        $dir = str_contains($basePath, '/') ? substr($basePath, 0, strrpos($basePath, '/')) : '';

        return $origin.$dir.'/'.$relative;
    }
}
