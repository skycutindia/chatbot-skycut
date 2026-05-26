<?php

namespace App\Mail;

use App\Models\OrganizationInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrganizationInvite $invite,
    ) {
        $this->invite->loadMissing(['organization', 'inviter']);
    }

    public function envelope(): Envelope
    {
        $org = $this->invite->organization;

        return new Envelope(
            subject: "You're invited to join {$org->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.organization-invite',
            with: [
                'invite' => $this->invite,
                'organization' => $this->invite->organization,
                'inviter' => $this->invite->inviter,
                'roleLabel' => \App\Enums\UserRole::from($this->invite->role)->label(),
                'acceptUrl' => route('team.invite.show', $this->invite->token),
                'expiresAt' => $this->invite->expires_at,
            ],
        );
    }
}
