<?php

namespace App\Support;

use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;

class DemoWebsiteRedirect
{
    public static function url(): ?string
    {
        $slug = config('chatbot.demo_website_slug');

        if (! $slug || ! Schema::hasTable('websites')) {
            return null;
        }

        $exists = Website::query()
            ->where('demo_slug', $slug)
            ->where('is_active', true)
            ->exists();

        return $exists ? route('demo.show', $slug) : null;
    }

    public static function redirect(): RedirectResponse
    {
        $demoUrl = self::url();

        if ($demoUrl) {
            return redirect()->to($demoUrl);
        }

        return redirect()->route('login');
    }
}
