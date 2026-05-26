<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;

class ChatReadReceiptService
{
    /** @param list<int> $messageIds */
    public function markDeliveredToVisitor(Conversation $conversation, array $messageIds): void
    {
        if ($messageIds === []) {
            return;
        }

        Message::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('id', $messageIds)
            ->whereIn('sender_type', ['agent', 'bot'])
            ->whereNull('delivered_at')
            ->update(['delivered_at' => now()]);
    }

    /** @param list<int> $messageIds */
    public function markReadByVisitor(Conversation $conversation, array $messageIds): void
    {
        if ($messageIds === []) {
            return;
        }

        Message::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('id', $messageIds)
            ->whereIn('sender_type', ['agent', 'bot'])
            ->whereNull('read_at')
            ->each(function (Message $message) {
                $message->update([
                    'delivered_at' => $message->delivered_at ?? now(),
                    'read_at' => now(),
                ]);
            });
    }

    /** @param list<int> $messageIds */
    public function markDeliveredToAgent(Conversation $conversation, array $messageIds): void
    {
        if ($messageIds === []) {
            return;
        }

        Message::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('id', $messageIds)
            ->where('sender_type', 'visitor')
            ->whereNull('delivered_at')
            ->update(['delivered_at' => now()]);
    }

    public function markReadByAgent(Conversation $conversation): void
    {
        Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_type', 'visitor')
            ->whereNull('read_at')
            ->each(function (Message $message) {
                $message->update([
                    'delivered_at' => $message->delivered_at ?? now(),
                    'read_at' => now(),
                ]);
            });
    }

    public function receiptStatus(Message $message): string
    {
        if ($message->read_at) {
            return 'read';
        }

        if ($message->delivered_at) {
            return 'delivered';
        }

        return 'sent';
    }
}
