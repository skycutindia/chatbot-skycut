<?php

namespace App\Services;

use App\Models\AgentQuickReply;
use App\Models\User;
use Illuminate\Support\Collection;

class AgentQuickReplyService
{
    /** @var list<array{title: string, body: string}> */
    private const DEFAULTS = [
        ['title' => 'Greeting', 'body' => 'Thanks for reaching out! How can I help you today?'],
        ['title' => 'Looking into it', 'body' => 'One moment while I look into that for you.'],
        ['title' => 'Anything else', 'body' => 'Is there anything else I can help with?'],
        ['title' => 'Sign-off', 'body' => 'Have a great day!'],
    ];

    public function forInbox(User $user): Collection
    {
        $this->ensureDefaults($user);

        return AgentQuickReply::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function ensureDefaults(User $user): void
    {
        $exists = AgentQuickReply::query()
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return;
        }

        foreach (self::DEFAULTS as $i => $row) {
            AgentQuickReply::create([
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'title' => $row['title'],
                'body' => $row['body'],
                'sort_order' => $i,
            ]);
        }
    }
}
