<?php

namespace App\Listeners;

use App\Events\ConversationAwaitingAgent;
use App\Services\ChatIntegrationNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendChatIntegrationNotifications implements ShouldQueue
{
    public function __construct(
        protected ChatIntegrationNotificationService $notifications,
    ) {}

    public function handle(ConversationAwaitingAgent $event): void
    {
        $this->notifications->notifyHandoff($event->conversation, $event->reason);
    }
}
