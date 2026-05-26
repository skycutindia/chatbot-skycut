<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WidgetModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_config_exposes_fullscreen_and_draggable_modules(): void
    {
        $org = Organization::create([
            'name' => 'API Org',
            'slug' => 'api-org',
            'is_active' => true,
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Module Site',
            'demo_slug' => 'module-site',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        $website->configuration->update([
            'enabled_modules' => array_merge($website->configuration->modules(), [
                'widget_fullscreen' => true,
                'widget_draggable' => true,
            ]),
        ]);

        Cache::forget("widget.config.{$website->bot_token}");

        $response = $this->getJson("/api/widget/{$website->bot_token}/config");

        $response->assertOk()
            ->assertJsonPath('modules.widget_fullscreen', true)
            ->assertJsonPath('modules.widget_draggable', true);
    }

    public function test_website_update_invalidates_widget_config_cache(): void
    {
        $org = Organization::create([
            'name' => 'Cache Org',
            'slug' => 'cache-org',
            'is_active' => true,
        ]);

        $user = \App\Models\User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'owner-modules@test.local',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Cache Site',
            'demo_slug' => 'cache-site',
            'is_active' => true,
        ]);

        $token = $website->bot_token;
        $this->getJson("/api/widget/{$token}/config")->assertOk();
        $this->assertTrue(Cache::has("widget.config.{$token}"));

        $before = (int) $website->fresh()->configuration->config_version;

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user)->put(route('websites.update', $website), [
            'name' => 'Cache Site',
            'bot_name' => 'Bot',
            'primary_color' => '#0d9488',
            'secondary_color' => '#14b8a6',
            'theme_mode' => 'light',
            'position' => 'right',
            'locale' => 'en',
            'ai_model' => 'gpt-4o-mini',
            'ai_temperature' => 0.7,
            'confidence_threshold' => 0.6,
            'rate_limit_per_minute' => 30,
            'rate_limit_per_hour' => 500,
            'modules' => array_merge($website->configuration->modules(), [
                'widget_fullscreen' => true,
            ]),
        ])->assertRedirect();

        $this->assertFalse(Cache::has("widget.config.{$token}"));
        $this->assertGreaterThan($before, (int) $website->fresh()->configuration->config_version);
    }
}
