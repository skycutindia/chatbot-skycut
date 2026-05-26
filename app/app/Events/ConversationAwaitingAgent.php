<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationAwaitingAgent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public string $reason = 'low_confidence',
    ) {
        $this->conversation->loadMissing('website');
    }

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('organization.'.$this->conversation->website->organization_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.awaiting_agent';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'website_id' => $this->conversation->website_id,
            'visitor_name' => $this->conversation->visitor_name,
            'reason' => $this->reason,
        ];
    }
}
