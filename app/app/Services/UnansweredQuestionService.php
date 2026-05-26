<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\QaPair;
use App\Models\UnansweredQuestion;
use App\Models\Website;

class UnansweredQuestionService
{
    public function record(
        Website $website,
        Conversation $conversation,
        string $visitorMessage,
        string $source = 'fallback',
        ?float $confidence = null,
    ): UnansweredQuestion {
        $normalized = mb_strtolower(trim($visitorMessage));

        $existing = UnansweredQuestion::query()
            ->where('website_id', $website->id)
            ->where('status', 'open')
            ->whereRaw('LOWER(visitor_message) = ?', [$normalized])
            ->first();

        if ($existing) {
            $existing->update([
                'conversation_id' => $conversation->id,
                'confidence' => $confidence ?? $existing->confidence,
                'source' => $source,
            ]);

            return $existing;
        }

        return UnansweredQuestion::create([
            'website_id' => $website->id,
            'conversation_id' => $conversation->id,
            'visitor_message' => $visitorMessage,
            'confidence' => $confidence,
            'source' => $source,
            'status' => 'open',
        ]);
    }

    public function promoteToQa(UnansweredQuestion $question, string $answer, ?string $category = null): QaPair
    {
        $pair = QaPair::create([
            'website_id' => $question->website_id,
            'question' => $question->visitor_message,
            'answer' => $answer,
            'category' => $category,
            'is_active' => true,
            'is_published' => true,
            'priority' => 10,
        ]);

        $question->update([
            'status' => 'resolved',
            'admin_answer' => $answer,
            'resolved_qa_pair_id' => $pair->id,
            'resolved_at' => now(),
        ]);

        app(WidgetConfigService::class)->invalidate($question->website);

        return $pair;
    }
}
