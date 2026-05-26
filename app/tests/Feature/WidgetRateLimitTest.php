<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function websiteWithTightLimit(): Website
    {
        $org = Organization::create([
            'name' => 'Rate Org',
            'slug' => 'rate-org',
            'is_active' => true,
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Rate Site',
            'demo_slug' => 'rate-site',
            'is_active' => true,
        ]);

        $website->configuration->update([
            'rate_limit_per_minute' => 1,
            'rate_limit_per_hour' => 100,
            'require_domain_validation' => false,
        ]);

        return $website->fresh();
    }

    public function test_widget_chat_returns_structured_429_when_rate_limited(): void
    {
        $website = $this->websiteWithTightLimit();
        $payload = [
            'message' => 'Hello',
            'visitor_id' => 'v_rate_test',
        ];

        $this->postJson("/api/widget/{$website->bot_token}/chat", $payload)->assertOk();

        $this->postJson("/api/widget/{$website->bot_token}/chat", $payload)
            ->assertStatus(429)
            ->assertJson([
                'error' => 'rate_limit_exceeded',
                'message' => 'You\'re sending messages too quickly. Please wait a moment and try again.',
            ]);
    }
}
