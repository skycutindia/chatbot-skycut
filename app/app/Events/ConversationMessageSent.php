<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
    ) {
        $this->message->loadMissing(['conversation.website', 'attachments']);
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        $orgId = $this->message->conversation->website->organization_id;

        return [
            new PrivateChannel('organization.'.$orgId),
            new PrivateChannel('conversation.'.$this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $attachments = app(\App\Services\ChatAttachmentService::class);

        return array_merge(
            $attachments->formatMessage($this->message),
            ['conversation_id' => $this->message->conversation_id, 'website_id' => $this->message->conversation->website_id]
        );
    }
}
