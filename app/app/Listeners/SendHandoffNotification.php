<?php

namespace App\Listeners;

use App\Events\ConversationAwaitingAgent;
use App\Mail\HandoffNotificationMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendHandoffNotification implements ShouldQueue
{
    public function handle(ConversationAwaitingAgent $event): void
    {
        $conversation = $event->conversation->loadMissing(['website.organization', 'assignedUser']);
        $organization = $conversation->website->organization;

        if (! $organization) {
            return;
        }

        $recipients = collect();

        $notificationEmail = $organization->settings['notification_email'] ?? null;
        if ($notificationEmail) {
            $recipients->push($notificationEmail);
        }

        if ($conversation->assignedUser?->email) {
            $recipients->push($conversation->assignedUser->email);
        }

        if ($recipients->isEmpty()) {
            $recipients = User::query()
                ->where('organization_id', $organization->id)
                ->where('is_active', true)
                ->whereIn('role', ['owner', 'admin', 'manager', 'agent'])
                ->pluck('email');
        }

        foreach ($recipients->filter()->unique() as $email) {
            Mail::to($email)->send(new HandoffNotificationMail($conversation, $event->reason));
        }
    }
}
