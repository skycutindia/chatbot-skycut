<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\OperatingHour;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OperatingHoursTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_update_syncs_operating_hours_and_widget_config(): void
    {
        $org = Organization::create([
            'name' => 'Hours Org',
            'slug' => 'hours-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'hours-owner@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Hours Site',
            'demo_slug' => 'hours-site',
            'is_active' => true,
        ]);

        $website->operatingHours()->where('day_of_week', now()->dayOfWeek)->update([
            'opens_at' => '23:59:00',
            'closes_at' => '23:59:59',
            'is_closed' => false,
        ]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user)->put(route('websites.update', $website), $this->basePayload($website, [
            'outside_hours_message' => 'We are closed for the day.',
            'hours_timezone' => 'UTC',
            'hours' => [
                now()->dayOfWeek => [
                    'is_closed' => '1',
                    'opens_at' => '09:00',
                    'closes_at' => '17:00',
                ],
            ],
        ]))->assertRedirect();

        $hour = $website->operatingHours()->where('day_of_week', now()->dayOfWeek)->first();
        $this->assertTrue($hour->is_closed);

        Cache::forget("widget.config.{$website->bot_token}");
        $this->getJson("/api/widget/{$website->bot_token}/config")
            ->assertOk()
            ->assertJsonPath('messages.outside_hours', 'We are closed for the day.')
            ->assertJsonPath('operating_hours.is_open', false);
    }

    public function test_widget_config_exposes_position_offsets(): void
    {
        $org = Organization::create([
            'name' => 'Offset Org',
            'slug' => 'offset-org',
            'is_active' => true,
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Offset Site',
            'demo_slug' => 'offset-site',
            'is_active' => true,
        ]);

        $website->configuration->update([
            'security_settings' => [
                'widget_offset_bottom' => 40,
                'widget_offset_side' => 16,
            ],
        ]);

        Cache::forget("widget.config.{$website->bot_token}");

        $this->getJson("/api/widget/{$website->bot_token}/config")
            ->assertOk()
            ->assertJsonPath('appearance.offset_bottom', 40)
            ->assertJsonPath('appearance.offset_side', 16);
    }

    /** @param array<string, mixed> $extra */
    protected function basePayload(Website $website, array $extra = []): array
    {
        $c = $website->configuration;

        return array_merge([
            'name' => $website->name,
            'is_active' => true,
            'bot_name' => $c->bot_name,
            'primary_color' => $c->primary_color,
            'secondary_color' => $c->secondary_color,
            'theme_mode' => $c->theme_mode,
            'position' => $c->position,
            'locale' => $c->locale,
            'ai_model' => $c->ai_model,
            'ai_temperature' => $c->ai_temperature,
            'confidence_threshold' => $c->confidence_threshold,
            'rate_limit_per_minute' => $c->rate_limit_per_minute,
            'rate_limit_per_hour' => $c->rate_limit_per_hour,
        ], $extra);
    }
}
