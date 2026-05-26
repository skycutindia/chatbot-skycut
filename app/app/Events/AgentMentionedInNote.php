<?php

namespace App\Events;

use App\Models\ConversationNoteMention;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentMentionedInNote
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ConversationNoteMention $mention,
    ) {
        $this->mention->loadMissing([
            'note.user',
            'conversation.website',
            'mentionedUser',
            'mentionedBy',
        ]);
    }
}
