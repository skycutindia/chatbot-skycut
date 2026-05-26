<?php

namespace Tests\Feature;

use App\Models\AllowedDomain;
use App\Models\Organization;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetDomainValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function websiteWithDomainLock(): Website
    {
        $org = Organization::create([
            'name' => 'Domain Org',
            'slug' => 'domain-org',
            'is_active' => true,
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Locked Site',
            'demo_slug' => 'locked-site',
            'is_active' => true,
        ]);

        $website->configuration->update(['require_domain_validation' => true]);
        AllowedDomain::create(['website_id' => $website->id, 'domain' => 'allowed.test']);

        return $website->fresh(['configuration', 'allowedDomains']);
    }

    public function test_widget_config_rejects_disallowed_origin(): void
    {
        $website = $this->websiteWithDomainLock();

        $this->getJson("/api/widget/{$website->bot_token}/config", [
            'Origin' => 'https://evil.test',
        ])
            ->assertForbidden()
            ->assertJson(['error' => 'Domain not allowed for this chatbot.']);
    }

    public function test_widget_config_allows_matching_origin(): void
    {
        $website = $this->websiteWithDomainLock();

        $this->getJson("/api/widget/{$website->bot_token}/config", [
            'Origin' => 'https://app.allowed.test',
        ])->assertOk();
    }

    public function test_widget_skips_validation_when_disabled(): void
    {
        $website = $this->websiteWithDomainLock();
        $website->configuration->update(['require_domain_validation' => false]);

        $this->getJson("/api/widget/{$website->bot_token}/config", [
            'Origin' => 'https://evil.test',
        ])->assertOk();
    }
}
