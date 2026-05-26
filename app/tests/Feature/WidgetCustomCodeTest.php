<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WidgetCustomCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_custom_css_and_js_flow_to_widget_config(): void
    {
        $org = Organization::create([
            'name' => 'CSS Org',
            'slug' => 'css-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'css-owner@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'CSS Site',
            'demo_slug' => 'css-site',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        $this->actingAs($user)
            ->put(route('websites.update', $website), [
                'name' => $website->name,
                'is_active' => 1,
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
                'custom_css' => '.chatflow-launcher { border: 3px solid gold; }',
                'custom_js' => 'console.log("custom");',
            ])
            ->assertRedirect();

        Cache::forget("widget.config.{$website->bot_token}");

        $this->getJson("/api/widget/{$website->bot_token}/config")
            ->assertOk()
            ->assertJsonPath('appearance.custom_css', '.chatflow-launcher { border: 3px solid gold; }')
            ->assertJsonPath('appearance.custom_js', 'console.log("custom");');
    }
}
