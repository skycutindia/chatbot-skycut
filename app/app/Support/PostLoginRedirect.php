<?php

namespace App\Support;

use App\Models\User;
use App\Models\Website;

class PostLoginRedirect
{
    public static function url(User $user): string
    {
        if ($user->roleEnum()->isPlatformLevel()) {
            return route('dashboard');
        }

        if ($user->roleEnum()->canHandleLiveChat() && ! $user->roleEnum()->canManageWebsites()) {
            return route('inbox.index');
        }

        $slug = config('chatbot.demo_website_slug');

        if ($slug && $user->organization_id && $user->roleEnum()->canManageWebsites()) {
            $website = Website::query()
                ->where('organization_id', $user->organization_id)
                ->where('demo_slug', $slug)
                ->first();

            if ($website) {
                return route('websites.index');
            }
        }

        return route('dashboard');
    }
}
