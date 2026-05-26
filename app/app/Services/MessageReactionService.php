<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReaction;
use Illuminate\Support\Collection;

class MessageReactionService
{
    /** @return list<string> */
    public function allowedEmojis(): array
    {
        return config('chatbot.reactions.emojis', ['👍', '❤️', '😂', '😮', '🙏']);
    }

    public function isAllowedEmoji(string $emoji): bool
    {
        return in_array($emoji, $this->allowedEmojis(), true);
    }

    /**
     * @return array{reactions: list<array{emoji: string, count: int, mine: bool}>, removed: bool}
     */
    public function toggleVisitorReaction(
        Conversation $conversation,
        Message $message,
        string $visitorId,
        string $emoji,
    ): array {
        abort_unless($message->conversation_id === $conversation->id, 404);
        abort_unless(in_array($message->sender_type, ['agent', 'bot'], true), 422);

        $existing = MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('reactor_type', 'visitor')
            ->where('reactor_key', $visitorId)
            ->first();

        $removed = false;

        if ($existing && $existing->emoji === $emoji) {
            $existing->delete();
            $removed = true;
        } else {
            MessageReaction::query()->updateOrCreate(
                [
                    'message_id' => $message->id,
                    'reactor_type' => 'visitor',
                    'reactor_key' => $visitorId,
                ],
                [
                    'conversation_id' => $conversation->id,
                    'emoji' => $emoji,
                ]
            );
        }

        return [
            'reactions' => $this->summarizeForMessage($message->id, 'visitor', $visitorId),
            'removed' => $removed,
        ];
    }

    /**
     * @return list<array{emoji: string, count: int, mine: bool}>
     */
    public function summarizeForMessage(int $messageId, ?string $reactorType = null, ?string $reactorKey = null): array
    {
        $rows = MessageReaction::query()->where('message_id', $messageId)->get();

        return $this->summarizeCollection($rows, $reactorType, $reactorKey);
    }

    /**
     * @return array<int, list<array{emoji: string, count: int, mine: bool}>>
     */
    public function summarizeForConversation(Conversation $conversation, ?string $reactorType = null, ?string $reactorKey = null): array
    {
        $rows = MessageReaction::query()
            ->where('conversation_id', $conversation->id)
            ->get()
            ->groupBy('message_id');

        $out = [];
        foreach ($rows as $messageId => $group) {
            $out[(int) $messageId] = $this->summarizeCollection($group, $reactorType, $reactorKey);
        }

        return $out;
    }

    /**
     * @param  Collection<int, MessageReaction>  $rows
     * @return list<array{emoji: string, count: int, mine: bool}>
     */
    protected function summarizeCollection(Collection $rows, ?string $reactorType, ?string $reactorKey): array
    {
        $grouped = $rows->groupBy('emoji');
        $summaries = [];

        foreach ($grouped as $emoji => $items) {
            $mine = $reactorType !== null
                && $reactorKey !== null
                && $items->contains(
                    fn (MessageReaction $r) => $r->reactor_type === $reactorType && $r->reactor_key === $reactorKey
                );

            $summaries[] = [
                'emoji' => (string) $emoji,
                'count' => $items->count(),
                'mine' => $mine,
            ];
        }

        usort($summaries, fn (array $a, array $b) => $b['count'] <=> $a['count']);

        return $summaries;
    }
}
