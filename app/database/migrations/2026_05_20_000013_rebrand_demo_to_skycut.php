<?php

use App\Models\Website;
use App\Services\WidgetConfigService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Website::query()->where('demo_slug', 'acme-store')->update([
            'name' => 'SkyCut',
            'demo_slug' => 'skycut',
        ]);

        Website::query()->where('demo_slug', 'skycut')->each(function (Website $website) {
            $website->configuration?->update([
                'bot_name' => 'SkyCut Assistant',
                'welcome_message' => 'Hi! I\'m SkyCut Assistant. Ask about pricing, features, or say "agent" to talk to our team.',
                'primary_color' => '#0d9488',
                'secondary_color' => '#14b8a6',
                'theme_mode' => 'light',
            ]);
            app(WidgetConfigService::class)->invalidate($website);
        });
    }

    public function down(): void
    {
        //
    }
};
