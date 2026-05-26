<?php

namespace App\Services;

use App\Models\ChatEvent;
use App\Models\Conversation;
use App\Models\ConversationRating;
use App\Models\FileAttachment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LiveChatAnalyticsService
{
    public function organizationSummary(?int $organizationId, int $days = 30): array
    {
        if (! $organizationId) {
            return [];
        }

        $since = now()->subDays($days);

        $baseQuery = Conversation::query()
            ->whereHas('website', fn ($q) => $q->where('organization_id', $organizationId))
            ->where('created_at', '>=', $since);

        $totalChats = (clone $baseQuery)->count();
        $activeChats = Conversation::query()
            ->whereHas('website', fn ($q) => $q->where('organization_id', $organizationId))
            ->whereIn('status', ['open', 'awaiting_agent', 'pending', 'waiting_visitor'])
            ->count();

        $humanChats = (clone $baseQuery)->where('mode', 'human')->count();
        $resolvedChats = (clone $baseQuery)->where('status', 'resolved')->count();
        $closedChats = (clone $baseQuery)->where('status', 'closed')->count();

        $avgFirstResponse = (clone $baseQuery)
            ->whereNotNull('first_response_at')
            ->get(['created_at', 'first_response_at'])
            ->avg(fn ($c) => $c->created_at->diffInMinutes($c->first_response_at));

        $avgResolution = (clone $baseQuery)
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at'])
            ->avg(fn ($c) => $c->created_at->diffInMinutes($c->resolved_at));

        $handoffs = ChatEvent::query()
            ->where('event_type', 'handoff')
            ->where('created_at', '>=', $since)
            ->whereHas('conversation.website', fn ($q) => $q->where('organization_id', $organizationId))
            ->count();

        $avgRating = ConversationRating::query()
            ->whereHas('conversation.website', fn ($q) => $q->where('organization_id', $organizationId))
            ->where('created_at', '>=', $since)
            ->avg('score');

        $attachments = FileAttachment::query()
            ->where('created_at', '>=', $since)
            ->whereHas('message.conversation.website', fn ($q) => $q->where('organization_id', $organizationId))
            ->count();

        $agentMessages = Message::query()
            ->where('sender_type', 'agent')
            ->where('created_at', '>=', $since)
            ->whereHas('conversation.website', fn ($q) => $q->where('organization_id', $organizationId))
            ->count();

        return [
            'period_days' => $days,
            'total_chats' => $totalChats,
            'active_chats' => $activeChats,
            'awaiting_agent' => Conversation::query()
                ->whereHas('website', fn ($q) => $q->where('organization_id', $organizationId))
                ->where('status', 'awaiting_agent')
                ->count(),
            'human_chats' => $humanChats,
            'handoff_rate' => $totalChats > 0 ? round(($humanChats / $totalChats) * 100, 1) : 0,
            'resolved_chats' => $resolvedChats,
            'closed_chats' => $closedChats,
            'resolution_rate' => $totalChats > 0 ? round((($resolvedChats + $closedChats) / $totalChats) * 100, 1) : 0,
            'avg_first_response_minutes' => $avgFirstResponse ? round((float) $avgFirstResponse, 1) : null,
            'avg_resolution_minutes' => $avgResolution ? round((float) $avgResolution, 1) : null,
            'handoffs' => $handoffs,
            'avg_satisfaction' => $avgRating ? round((float) $avgRating, 1) : null,
            'attachments' => $attachments,
            'agent_messages' => $agentMessages,
        ];
    }

    /** @return list<array{name: string, assigned: int, messages: int, avg_rating: float|null}> */
    public function agentPerformance(?int $organizationId, int $days = 30): array
    {
        if (! $organizationId) {
            return [];
        }

        $since = now()->subDays($days);

        $agents = User::query()
            ->where('organization_id', $organizationId)
            ->whereIn('role', ['agent', 'manager', 'admin', 'owner'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return $agents->map(function (User $agent) use ($since) {
            $assigned = Conversation::query()
                ->where('assigned_user_id', $agent->id)
                ->where('created_at', '>=', $since)
                ->count();

            $messages = Message::query()
                ->where('sender_type', 'agent')
                ->where('sender_id', $agent->id)
                ->where('created_at', '>=', $since)
                ->count();

            $avgRating = ConversationRating::query()
                ->whereHas('conversation', fn ($q) => $q->where('assigned_user_id', $agent->id))
                ->where('created_at', '>=', $since)
                ->avg('score');

            return [
                'name' => $agent->name,
                'assigned' => $assigned,
                'messages' => $messages,
                'avg_rating' => $avgRating ? round((float) $avgRating, 1) : null,
            ];
        })->sortByDesc('messages')->values()->all();
    }

    /** @return list<array{date: string, label: string, count: int, height_pct: int}> */
    public function chatsPerDay(?int $organizationId, int $days = 7): array
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

    /** @return list<array{status: string, total: int}> */
    public function statusBreakdown(?int $organizationId): array
    {
        if (! $organizationId) {
            return [];
        }

        return Conversation::query()
            ->whereHas('website', fn ($q) => $q->where('organization_id', $organizationId))
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['status' => $row->status, 'total' => (int) $row->total])
            ->all();
    }
}
