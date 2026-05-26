<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\KnowledgeArticle;
use App\Models\Message;
use App\Models\PlatformSetting;
use App\Models\QaPair;
use App\Models\TriggerKeyword;
use App\Models\Website;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatResponseService
{
    public function __construct(
        protected WidgetConfigService $configService,
        protected LiveHandoffService $handoffService,
        protected EmbeddingService $embeddings,
        protected UnansweredQuestionService $unanswered,
    ) {}

    public function respond(Website $website, Conversation $conversation, string $userMessage): array
    {
        $config = $website->configuration;
        $normalized = Str::lower(trim($userMessage));

        $qaMatch = $this->matchQaPair($website, $normalized);
        if ($qaMatch) {
            return $this->storeBotReply($conversation, $qaMatch, 1.0, 'qa_pair');
        }

        $keywordMatch = $this->matchTriggerKeyword($website, $normalized);
        if ($keywordMatch) {
            return $this->storeBotReply($conversation, $keywordMatch['response'], 1.0, 'trigger_keyword');
        }

        if ($this->handoffService->shouldHandoff($website, $conversation, $userMessage, 0.0, 'keyword')) {
            $this->handoffService->initiate($website, $conversation, 'visitor_request');

            return array_merge(
                $this->storeBotReply($conversation, 'Connecting you with a live agent now.', 1.0, 'handoff'),
                ['handoff' => true, 'status' => 'awaiting_agent']
            );
        }

        $kbMatch = $this->searchKnowledge($website, $userMessage);
        if ($kbMatch && $kbMatch['score'] >= (float) $config->confidence_threshold) {
            return $this->storeBotReply($conversation, $kbMatch['answer'], $kbMatch['score'], 'knowledge_base');
        }

        if (! $config->ai_enabled) {
            $this->unanswered->record($website, $conversation, $userMessage, 'ai_disabled', 0.0);

            return $this->storeBotReply(
                $conversation,
                $config->fallback_message ?? $config->offline_message ?? 'Thanks for your message. A team member will follow up soon.',
                0.5,
                'fallback'
            );
        }

        return $this->generateAiReply($website, $conversation, $userMessage, $kbMatch);
    }

    protected function matchQaPair(Website $website, string $message): ?string
    {
        foreach ($website->qaPairs()->where('is_active', true)->where('is_published', true)->orderByDesc('priority')->get() as $pair) {
            if (Str::contains($message, Str::lower($pair->question))) {
                return $pair->answer;
            }
            foreach ($pair->question_variations ?? [] as $variation) {
                if (Str::contains($message, Str::lower($variation))) {
                    return $pair->answer;
                }
            }
            foreach ($pair->trigger_keywords ?? [] as $keyword) {
                if (Str::contains($message, Str::lower($keyword))) {
                    return $pair->answer;
                }
            }
        }

        return null;
    }

    protected function matchTriggerKeyword(Website $website, string $message): ?array
    {
        foreach ($website->triggerKeywords()->where('is_active', true)->get() as $trigger) {
            if (Str::contains($message, Str::lower($trigger->keyword))) {
                return ['response' => $trigger->response ?? ''];
            }
        }

        return null;
    }

    protected function searchKnowledge(Website $website, string $query): ?array
    {
        $semantic = $this->embeddings->search($website, $query);
        if ($semantic) {
            return $semantic;
        }

        $articles = KnowledgeArticle::query()
            ->where('website_id', $website->id)
            ->where('is_published', true)
            ->where(function ($q) use ($query) {
                if (KnowledgeArticle::query()->getConnection()->getDriverName() === 'mysql') {
                    $q->whereFullText(['title', 'content'], $query);
                }
                $q->orWhere('title', 'like', '%'.$query.'%')
                    ->orWhere('content', 'like', '%'.$query.'%');
            })
            ->limit(3)
            ->get();

        if ($articles->isEmpty()) {
            $articles = KnowledgeArticle::query()
                ->where('website_id', $website->id)
                ->where('is_published', true)
                ->where('title', 'like', '%'.$query.'%')
                ->limit(3)
                ->get();
        }

        $best = $articles->first();
        if (! $best) {
            return null;
        }

        return [
            'answer' => Str::limit(strip_tags($best->content), 800),
            'score' => 0.85,
            'article_id' => $best->id,
        ];
    }

    protected function generateAiReply(Website $website, Conversation $conversation, string $userMessage, ?array $kbMatch): array
    {
        $config = $website->configuration;
        $apiKey = app(AiConfigService::class)->resolveApiKey($website->organization);

        if (! $apiKey) {
            return $this->storeBotReply(
                $conversation,
                'AI is not configured. Please contact support.',
                0.0,
                'ai_unconfigured'
            );
        }

        $history = $conversation->messages()
            ->latest()
            ->limit(12)
            ->get()
            ->reverse()
            ->map(fn (Message $m) => [
                'role' => $m->sender_type === 'visitor' ? 'user' : 'assistant',
                'content' => $m->content,
            ])
            ->values()
            ->all();

        $context = $kbMatch['answer'] ?? '';
        $system = $config->system_prompt ?? config('chatbot.default_system_prompt');
        if ($context) {
            $system .= "\n\nRelevant knowledge:\n".$context;
        }

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post(app(AiConfigService::class)->resolveBaseUrl().'/chat/completions', [
                'model' => $config->ai_model ?: app(AiConfigService::class)->resolveDefaultModel($website->organization),
                'temperature' => (float) $config->ai_temperature,
                'max_tokens' => (int) ($config->max_tokens ?? 1024),
                'messages' => array_merge(
                    [['role' => 'system', 'content' => $system]],
                    $history,
                    [['role' => 'user', 'content' => $userMessage]]
                ),
            ]);

        if (! $response->successful()) {
            return $this->storeBotReply(
                $conversation,
                'Sorry, I could not process that right now. Please try again.',
                0.0,
                'ai_error'
            );
        }

        $content = $response->json('choices.0.message.content', '');
        $confidence = $kbMatch['score'] ?? (strlen(trim($content)) > 20 ? 0.85 : 0.45);

        if ($confidence < (float) $config->confidence_threshold) {
            $this->unanswered->record($website, $conversation, $userMessage, 'low_confidence', $confidence);
        }

        if ($this->handoffService->shouldHandoff($website, $conversation, $userMessage, $confidence, 'ai')) {
            $this->handoffService->initiate($website, $conversation, 'low_confidence');
            $handoffMessage = $website->configuration->offline_message
                ?? 'Connecting you with a live agent. Please hold on.';

            return array_merge(
                $this->storeBotReply($conversation, $handoffMessage, $confidence, 'handoff'),
                ['handoff' => true, 'status' => 'awaiting_agent']
            );
        }

        return $this->storeBotReply($conversation, $content, $confidence, 'ai');
    }

    protected function storeBotReply(Conversation $conversation, string $content, float $confidence, string $source): array
    {
        $message = $conversation->messages()->create([
            'sender_type' => 'bot',
            'content' => $content,
            'confidence' => $confidence,
            'source' => $source,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return [
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'sender_type' => 'bot',
                'confidence' => $confidence,
                'source' => $source,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ];
    }
}
