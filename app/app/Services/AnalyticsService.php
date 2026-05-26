<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Conversation;
use App\Models\ConversationRating;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Website;
use Illuminate\Http\Request;

class AnalyticsService
{
    public function track(Website $website, string $eventType, Request $request, array $payload = []): void
    {
        AnalyticsEvent::create([
            'website_id' => $website->id,
            'event_type' => $eventType,
            'visitor_id' => $payload['visitor_id'] ?? null,
            'conversation_id' => $payload['conversation_id'] ?? null,
            'payload' => $payload,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function summary(Website $website, int $days = 30): array
    {
        $since = now()->subDays($days);

        $events = AnalyticsEvent::query()
            ->where('website_id', $website->id)
            ->where('created_at', '>=', $since)
            ->selectRaw('event_type, COUNT(*) as total')
            ->groupBy('event_type')
            ->pluck('total', 'event_type');

        $conversations = $website->conversations()
            ->where('created_at', '>=', $since)
            ->count();

        $openConversations = $website->conversations()
            ->whereIn('status', ['open', 'awaiting_agent'])
            ->count();

        $handoffs = $website->conversations()
            ->where('created_at', '>=', $since)
            ->where('mode', 'human')
            ->count();

        $messages = Message::query()
            ->whereHas('conversation', fn ($q) => $q->where('website_id', $website->id)->where('created_at', '>=', $since))
            ->count();

        $botMessages = Message::query()
            ->whereHas('conversation', fn ($q) => $q->where('website_id', $website->id)->where('created_at', '>=', $since))
            ->where('sender_type', 'bot')
            ->count();

        $leads = Lead::query()
            ->where('website_id', $website->id)
            ->where('created_at', '>=', $since)
            ->count();

        $aiResolved = Message::query()
            ->whereHas('conversation', fn ($q) => $q->where('website_id', $website->id)->where('created_at', '>=', $since))
            ->where('sender_type', 'bot')
            ->whereIn('source', ['ai', 'qa_pair', 'knowledge_base', 'trigger_keyword'])
            ->count();

        $avgRating = ConversationRating::query()
            ->whereHas('conversation', fn ($q) => $q->where('website_id', $website->id)->where('created_at', '>=', $since))
            ->avg('score');

        return [
            'period_days' => $days,
            'conversations' => $conversations,
            'open_conversations' => $openConversations,
            'messages' => $messages,
            'leads' => $leads,
            'handoffs' => $handoffs,
            'handoff_rate' => $conversations > 0 ? round(($handoffs / $conversations) * 100, 1) : 0,
            'ai_resolution_rate' => $botMessages > 0 ? round(($aiResolved / $botMessages) * 100, 1) : 0,
            'avg_satisfaction' => $avgRating ? round((float) $avgRating, 1) : null,
            'events' => $events,
            'widget_opens' => (int) ($events['widget_open'] ?? 0),
            'widget_loads' => (int) ($events['widget_loaded'] ?? 0),
            'chats_started' => (int) ($events['message_sent'] ?? 0),
        ];
    }

    /** @return list<array{date: string, label: string, count: int}> */
    public function conversationsPerDay(Website $website, int $days = 7): array
    {
        $result = [];
        $max = 1;

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = $website->conversations()
                ->whereDate('created_at', $date->toDateString())
                ->count();
            $max = max($max, $count);
            $result[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('D'),
                'count' => $count,
            ];
        }

        foreach ($result as &$row) {
            $row['height_pct'] = $max > 0 ? round(($row['count'] / $max) * 100) : 0;
        }

        return $result;
    }

    /** @return list<array{date: string, label: string, count: int, height_pct: int}> */
    public function organizationConversationsPerDay(?int $organizationId, int $days = 7): array
    {
        if (! $organizationId) {
            return [];
        }

        $result = [];
        $max = 1;

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = Conversation::query()
                ->whereDate('created_at', $date->toDateString())
                ->whereHas('website', fn ($q) => $q->where('organization_id', $organizationId))
                ->count();
            $max = max($max, $count);
            $result[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('D'),
                'count' => $count,
            ];
        }

        foreach ($result as &$row) {
            $row['height_pct'] = $max > 0 ? round(($row['count'] / $max) * 100) : 0;
        }

        return $result;
    }

    /** @return array{chats_today: int, leads_today: int, avg_csat: ?float} */
    public function organizationTodayStats(?int $organizationId): array
    {
        if (! $organizationId) {
            return ['chats_today' => 0, 'leads_today' => 0, 'avg_csat' => null];
        }

        $today = now()->toDateString();

        $chatsToday = Conversation::query()
            ->whereDate('created_at', $today)
            ->whereHas('website', fn ($q) => $q->where('organization_id', $organizationId))
            ->count();

        $leadsToday = Lead::query()
            ->where('organization_id', $organizationId)
            ->whereDate('created_at', $today)
            ->count();

        $avgCsat = ConversationRating::query()
            ->whereDate('created_at', $today)
            ->whereHas('conversation.website', fn ($q) => $q->where('organization_id', $organizationId))
            ->avg('score');

        return [
            'chats_today' => $chatsToday,
            'leads_today' => $leadsToday,
            'avg_csat' => $avgCsat ? round((float) $avgCsat, 1) : null,
        ];
    }
}
