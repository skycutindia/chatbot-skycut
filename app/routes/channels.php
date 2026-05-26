<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('organization.{organizationId}', function ($user, $organizationId) {
    if ($user->roleEnum()->isPlatformLevel()) {
        return true;
    }

    return (int) $user->organization_id === (int) $organizationId;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::with('website')->find($conversationId);

    if (! $conversation) {
        return false;
    }

    if ($user->roleEnum()->isPlatformLevel()) {
        return true;
    }

    return (int) $user->organization_id === (int) $conversation->website->organization_id;
});
