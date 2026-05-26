<?php

namespace App\Mail;

use App\Models\ConversationNoteMention;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentMentionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ConversationNoteMention $mention,
    ) {}

    public function envelope(): Envelope
    {
        $author = $this->mention->mentionedBy?->name ?? 'A teammate';
        $visitor = $this->mention->conversation?->visitor_name ?: 'a visitor';

        return new Envelope(
            subject: "{$author} mentioned you in a live chat ({$visitor})",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.agent-mention', ['mention' => $this->mention])->render(),
        );
    }
}
