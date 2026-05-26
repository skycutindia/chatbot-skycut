<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WidgetSoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_config_exposes_sound_enabled_flag(): void
    {
        $org = Organization::create([
            'name' => 'Sound Org',
            'slug' => 'sound-org',
            'is_active' => true,
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Sound Site',
            'demo_slug' => 'sound-site',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        $website->configuration->update(['sound_enabled' => false]);
        Cache::forget("widget.config.{$website->bot_token}");

        $this->getJson("/api/widget/{$website->bot_token}/config")
            ->assertOk()
            ->assertJsonPath('appearance.sound_enabled', false);
    }
}
