<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public string $action = 'updated',
    ) {
        $this->conversation->loadMissing('website');
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        $orgId = $this->conversation->website->organization_id;

        return [
            new PrivateChannel('organization.'.$orgId),
            new PrivateChannel('conversation.'.$this->conversation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'website_id' => $this->conversation->website_id,
            'status' => $this->conversation->status,
            'mode' => $this->conversation->mode,
            'action' => $this->action,
            'visitor_name' => $this->conversation->visitor_name,
            'assigned_user_id' => $this->conversation->assigned_user_id,
            'last_message_at' => $this->conversation->last_message_at?->toIso8601String(),
        ];
    }
}
