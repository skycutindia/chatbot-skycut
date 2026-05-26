<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ConversationQueryService
{
    public function forOrganization(User $user, Request $request): Builder
    {
        $query = Conversation::query()
            ->with(['website', 'assignedUser', 'latestMessage'])
            ->visibleInInbox();

        if ($user->organization_id) {
            $query->whereHas('website', fn ($q) => $q->where('organization_id', $user->organization_id));
        }

        if ($user->roleEnum()->value === 'agent') {
            $query->where(function ($inner) use ($user) {
                $inner->whereNull('assigned_user_id')->orWhere('assigned_user_id', $user->id);
            });
        }

        $this->applyFilters($query, $request);

        return $this->applySort($query, $request);
    }

    protected function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('website_id')) {
            $query->where('website_id', $request->integer('website_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        } elseif ($request->get('view') === 'archive') {
            $query->whereIn('status', ['closed', 'resolved']);
        } elseif ($request->get('view') !== 'all') {
            $query->whereIn('status', ['open', 'awaiting_agent', 'pending', 'waiting_visitor']);
        }

        if ($request->boolean('starred')) {
            $query->where('is_starred', true);
        }

        if ($request->boolean('pinned')) {
            $query->where('is_pinned', true);
        }

        if ($request->boolean('awaiting')) {
            $query->where('status', 'awaiting_agent');
        }

        if ($request->filled('assigned')) {
            if ($request->assigned === 'unassigned') {
                $query->whereNull('assigned_user_id');
            } elseif ($request->assigned === 'me') {
                $query->where('assigned_user_id', $request->user()->id);
            }
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if ($search = trim((string) $request->get('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_email', 'like', "%{$search}%")
                    ->orWhere('visitor_phone', 'like', "%{$search}%")
                    ->orWhere('visitor_id', 'like', "%{$search}%")
                    ->orWhereHas('messages', fn ($m) => $m->where('content', 'like', "%{$search}%"));
            });
        }
    }

    protected function applySort(Builder $query, Request $request): Builder
    {
        return match ($request->get('sort', 'newest')) {
            'oldest' => $query->orderBy('last_message_at'),
            'priority' => $query->orderByRaw("CASE priority WHEN 'urgent' THEN 5 WHEN 'high' THEN 4 WHEN 'medium' THEN 3 WHEN 'normal' THEN 2 ELSE 1 END DESC")
                ->orderByDesc('last_message_at'),
            'assigned' => $query->orderByRaw('assigned_user_id IS NULL DESC')->orderByDesc('last_message_at'),
            default => $query->orderByDesc('is_pinned')->orderByDesc('last_message_at'),
        };
    }
}
