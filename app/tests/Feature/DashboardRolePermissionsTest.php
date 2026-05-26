<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    /** @return array{Organization, Website, User} */
    protected function fixtures(): array
    {
        $org = Organization::create([
            'name' => 'Perm Org',
            'slug' => 'perm-org',
            'is_active' => true,
        ]);

        $owner = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'perm-owner@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Perm Site',
            'demo_slug' => 'perm-site',
            'is_active' => true,
        ]);

        return [$org, $website, $owner];
    }

    protected function userInOrg(Organization $org, UserRole $role): User
    {
        return User::create([
            'organization_id' => $org->id,
            'name' => $role->label(),
            'email' => strtolower($role->value).'@perm.test',
            'password' => bcrypt('password'),
            'role' => $role->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_agent_cannot_create_or_update_websites(): void
    {
        [$org, $website] = array_slice($this->fixtures(), 0, 2);
        $agent = $this->userInOrg($org, UserRole::Agent);

        $this->actingAs($agent)
            ->get(route('websites.create'))
            ->assertForbidden();

        $this->actingAs($agent)
            ->put(route('websites.update', $website), ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_manager_cannot_update_organization_settings(): void
    {
        [$org] = array_slice($this->fixtures(), 0, 1);
        $manager = $this->userInOrg($org, UserRole::Manager);

        $this->actingAs($manager)
            ->get(route('settings.organization.edit'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->put(route('settings.organization.update'), ['name' => 'Renamed'])
            ->assertForbidden();
    }

    public function test_agent_cannot_update_organization_settings(): void
    {
        [$org] = array_slice($this->fixtures(), 0, 1);
        $agent = $this->userInOrg($org, UserRole::Agent);

        $this->actingAs($agent)
            ->put(route('settings.organization.update'), [
                'name' => 'Agent Org',
                'timezone' => 'UTC',
            ])
            ->assertForbidden();
    }

    public function test_viewer_cannot_assign_inbox_conversation(): void
    {
        [$org, $website] = array_slice($this->fixtures(), 0, 2);
        $viewer = $this->userInOrg($org, UserRole::Viewer);

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v_perm',
            'status' => 'awaiting_agent',
            'mode' => 'human',
            'last_message_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->post(route('inbox.assign', $conversation))
            ->assertForbidden();
    }

    public function test_manager_can_access_website_hub(): void
    {
        [$org, $website] = array_slice($this->fixtures(), 0, 2);
        $manager = $this->userInOrg($org, UserRole::Manager);

        $this->actingAs($manager)
            ->get(route('websites.hub', $website))
            ->assertRedirect(route('websites.index', ['manage' => $website->id]));
    }
}
