<?php

namespace App\Mail;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HandoffNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public string $reason,
    ) {
        $this->conversation->loadMissing(['website', 'messages' => fn ($q) => $q->latest()->limit(3)]);
    }

    public function envelope(): Envelope
    {
        $website = $this->conversation->website;

        return new Envelope(
            subject: "Live chat awaiting agent — {$website->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.handoff-notification',
            with: [
                'conversation' => $this->conversation,
                'website' => $this->conversation->website,
                'reason' => $this->reason,
                'inboxUrl' => url('/inbox'),
                'chatUrl' => url("/websites/{$this->conversation->website_id}/conversations/{$this->conversation->id}"),
            ],
        );
    }
}
