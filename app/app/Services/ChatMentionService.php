<?php

namespace App\Services;

use App\Events\AgentMentionedInNote;
use App\Models\Conversation;
use App\Models\ConversationNote;
use App\Models\ConversationNoteMention;
use App\Models\User;
use Illuminate\Support\Collection;

class ChatMentionService
{
    /** @return Collection<int, User> */
    public function mentionableAgents(int $organizationId, ?string $query = null): Collection
    {
        return User::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->whereIn('role', ['agent', 'manager', 'admin', 'owner'])
            ->when($query, fn ($q) => $q->where(function ($inner) use ($query) {
                $inner->where('name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%');
            }))
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'email', 'role']);
    }

    /** @return Collection<int, User> */
    public function resolveMentionedUsers(string $body, int $organizationId, ?int $excludeUserId = null): Collection
    {
        $agents = $this->mentionableAgents($organizationId);

        if ($excludeUserId) {
            $agents = $agents->where('id', '!=', $excludeUserId);
        }

        $mentioned = collect();

        foreach ($agents->sortByDesc(fn (User $user) => strlen($user->name)) as $user) {
            $pattern = '/@'.preg_quote($user->name, '/').'(?=\s|[,.!?]|$)/u';
            if (preg_match($pattern, $body)) {
                $mentioned->push($user);
            }
        }

        return $mentioned->unique('id')->values();
    }

    public function syncNoteMentions(ConversationNote $note, Conversation $conversation, User $author): void
    {
        $conversation->loadMissing('website');

        $mentioned = $this->resolveMentionedUsers(
            $note->body,
            (int) $conversation->website->organization_id,
            $author->id
        );

        foreach ($mentioned as $user) {
            $mention = ConversationNoteMention::firstOrCreate(
                [
                    'conversation_note_id' => $note->id,
                    'mentioned_user_id' => $user->id,
                ],
                [
                    'conversation_id' => $conversation->id,
                    'mentioned_by_user_id' => $author->id,
                ]
            );

            if ($mention->wasRecentlyCreated) {
                event(new AgentMentionedInNote($mention));
            }
        }
    }

    public function formatBodyHtml(string $body): string
    {
        $escaped = e($body);

        return (string) preg_replace(
            '/@([A-Za-z0-9][A-Za-z0-9._\- ]*)/u',
            '<span class="lc-mention">@$1</span>',
            $escaped
        );
    }

    public function unreadCount(User $user): int
    {
        return ConversationNoteMention::query()
            ->where('mentioned_user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markReadForConversation(User $user, Conversation $conversation): void
    {
        ConversationNoteMention::query()
            ->where('mentioned_user_id', $user->id)
            ->where('conversation_id', $conversation->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
