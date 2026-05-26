<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ConversationExportService
{
    public function __construct(
        protected ConversationQueryService $queryService,
    ) {}

    public function exportQuery(User $user, Request $request): Builder
    {
        return $this->queryService
            ->forOrganization($user, $request)
            ->with(['website', 'assignedUser', 'rating'])
            ->withCount('messages');
    }

    /** @return list<string> */
    public function csvHeaders(): array
    {
        return [
            'ID',
            'Website',
            'Visitor name',
            'Visitor email',
            'Visitor phone',
            'Status',
            'Mode',
            'Channel',
            'Assigned to',
            'Priority',
            'CSAT score',
            'CSAT comment',
            'Messages',
            'Started',
            'Last message',
        ];
    }

    /** @return list<int|string|null> */
    public function csvRow(Conversation $conversation): array
    {
        return [
            $conversation->id,
            $conversation->website?->name,
            $conversation->visitor_name,
            $conversation->visitor_email,
            $conversation->visitor_phone,
            $conversation->status,
            $conversation->mode,
            $conversation->channel,
            $conversation->assignedUser?->name,
            $conversation->priority,
            $conversation->rating?->score,
            $conversation->rating?->comment,
            $conversation->messages_count,
            $conversation->created_at?->toDateTimeString(),
            $conversation->last_message_at?->toDateTimeString(),
        ];
    }
}
