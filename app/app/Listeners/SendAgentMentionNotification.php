<?php

namespace App\Listeners;

use App\Events\AgentMentionedInNote;
use App\Mail\AgentMentionMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendAgentMentionNotification implements ShouldQueue
{
    public function handle(AgentMentionedInNote $event): void
    {
        $mention = $event->mention;
        $email = $mention->mentionedUser?->email;

        if (! $email) {
            return;
        }

        Mail::to($email)->send(new AgentMentionMail($mention));
    }
}
