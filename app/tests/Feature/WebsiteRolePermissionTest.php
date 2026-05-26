<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function websiteForOrg(Organization $org): Website
    {
        return Website::create([
            'organization_id' => $org->id,
            'name' => 'Perm Site',
            'demo_slug' => 'perm-site',
            'is_active' => true,
        ]);
    }

    public function test_viewer_cannot_update_website_settings(): void
    {
        $org = Organization::create([
            'name' => 'Perm Org',
            'slug' => 'perm-org',
            'is_active' => true,
        ]);

        $viewer = User::create([
            'organization_id' => $org->id,
            'name' => 'Viewer',
            'email' => 'viewer-perm@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Viewer->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = $this->websiteForOrg($org);

        $this->actingAs($viewer)
            ->put(route('websites.update', $website), [
                'name' => 'Hacked',
                'bot_name' => 'Bot',
                'primary_color' => '#000000',
                'secondary_color' => '#ffffff',
                'theme_mode' => 'light',
                'position' => 'right',
                'locale' => 'en',
                'ai_model' => 'gpt-4o-mini',
                'ai_temperature' => 0.7,
                'confidence_threshold' => 0.5,
                'rate_limit_per_minute' => 30,
                'rate_limit_per_hour' => 500,
            ])
            ->assertForbidden();
    }

    public function test_agent_cannot_access_organization_settings(): void
    {
        $org = Organization::create([
            'name' => 'Settings Org',
            'slug' => 'settings-org',
            'is_active' => true,
        ]);

        $agent = User::create([
            'organization_id' => $org->id,
            'name' => 'Agent',
            'email' => 'agent-settings@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($agent)
            ->get(route('settings.organization.edit'))
            ->assertForbidden();
    }

    public function test_user_cannot_view_website_from_another_organization(): void
    {
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'is_active' => true]);
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b', 'is_active' => true]);

        $userA = User::create([
            'organization_id' => $orgA->id,
            'name' => 'Owner A',
            'email' => 'owner-a@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $websiteB = $this->websiteForOrg($orgB);

        $this->actingAs($userA)
            ->get(route('websites.show', $websiteB))
            ->assertForbidden();
    }
}
