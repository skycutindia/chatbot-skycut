<?php

namespace App\Services;

use App\Models\Website;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class UrlQaGeneratorService
{
    public function __construct(
        protected WebsiteCrawlerService $crawler,
    ) {}

    /**
     * @return array{url: string, title: string, pairs: list<array{question: string, answer: string, trigger_keywords: list<string>}>}
     */
    public function generateFromUrl(Website $website, string $url, int $maxPairs = 8): array
    {
        $page = $this->crawler->fetchSinglePage($url);
        $pairs = $this->generatePairsWithAi($website, $page['url'], $page['title'], $page['text'], $maxPairs);

        return [
            'url' => $page['url'],
            'title' => $page['title'],
            'pairs' => $pairs,
        ];
    }

    /**
     * @return list<array{question: string, answer: string, trigger_keywords: list<string>}>
     */
    protected function generatePairsWithAi(Website $website, string $url, string $title, string $text, int $maxPairs): array
    {
        $ai = app(AiConfigService::class);
        $apiKey = $ai->resolveApiKey($website->organization);
        if (! $apiKey) {
            throw new \RuntimeException('OpenAI API key is not configured. Add it in Settings → AI & ChatGPT.');
        }

        $maxPairs = max(3, min(15, $maxPairs));
        $botName = $website->configuration?->bot_name ?? $website->name;

        $system = <<<PROMPT
You are a training assistant for "{$botName}". Read the website content and produce FAQ-style Q&A pairs for a chatbot.
Return ONLY valid JSON with this shape:
{"pairs":[{"question":"...","answer":"...","trigger_keywords":["word1","word2"]}]}
Rules:
- Create {$maxPairs} diverse pairs (or fewer if content is thin).
- Answers must be factual and based ONLY on the provided content.
- trigger_keywords: 2-5 short words/phrases a visitor might type to match this Q&A (lowercase, no duplicates).
- Questions should sound natural, under 200 characters.
- Answers under 800 characters, helpful and professional.
PROMPT;

        $user = "Page URL: {$url}\nPage title: {$title}\n\nContent:\n{$text}";

        $response = Http::withToken($apiKey)
            ->timeout(90)
            ->post($ai->resolveBaseUrl().'/chat/completions', [
                'model' => $website->configuration?->ai_model
                    ?: $ai->resolveDefaultModel($website->organization),
                'temperature' => 0.35,
                'max_tokens' => 4096,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if (! $response->successful()) {
            $msg = $response->json('error.message') ?? 'OpenAI request failed.';
            throw new \RuntimeException(is_string($msg) ? $msg : 'OpenAI request failed.');
        }

        $raw = $response->json('choices.0.message.content', '');
        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || ! isset($decoded['pairs']) || ! is_array($decoded['pairs'])) {
            throw new \RuntimeException('AI returned an invalid response. Try again.');
        }

        $pairs = [];
        foreach ($decoded['pairs'] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $question = trim((string) ($row['question'] ?? ''));
            $answer = trim((string) ($row['answer'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }
            $keywords = $this->normalizeKeywords($row['trigger_keywords'] ?? []);
            $pairs[] = [
                'question' => Str::limit($question, 500, ''),
                'answer' => Str::limit($answer, 5000, ''),
                'trigger_keywords' => $keywords,
            ];
            if (count($pairs) >= $maxPairs) {
                break;
            }
        }

        if ($pairs === []) {
            throw new \RuntimeException('No Q&A pairs could be generated from this page.');
        }

        return $pairs;
    }

    /** @param mixed $raw */
    protected function normalizeKeywords(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/[,;]+/', $raw) ?: [];
        }
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $kw) {
            $kw = mb_strtolower(trim((string) $kw));
            if ($kw !== '' && ! in_array($kw, $out, true)) {
                $out[] = Str::limit($kw, 64, '');
            }
        }

        return array_slice($out, 0, 8);
    }
}
