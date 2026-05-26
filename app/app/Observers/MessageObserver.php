<?php

namespace App\Observers;

use App\Events\ConversationMessageSent;
use App\Models\Message;

class MessageObserver
{
    public function created(Message $message): void
    {
        if ($message->sender_type === 'visitor') {
            $message->conversation?->increment('agent_unread_count');
        }

        if (config('broadcasting.default') !== 'null' && $message->source !== 'attachment') {
            broadcast(new ConversationMessageSent($message));
        }

        if ($message->sender_type === 'visitor') {
            app(\App\Services\ChatAutomationService::class)->applyForVisitorMessage($message);

            $conversation = $message->conversation;
            if ($conversation && $conversation->status === 'awaiting_agent') {
                app(\App\Services\ChatIntegrationNotificationService::class)
                    ->notifyNewVisitorMessage($conversation, $message);
            }
        }

        if ($message->sender_type === 'agent' && $message->source === 'live_agent') {
            $conversation = $message->conversation?->loadMissing('website');

            if ($conversation?->isWhatsApp()) {
                app(\App\Services\WhatsAppService::class)->sendAgentReply($conversation, $message);
            }
        }
    }
}
