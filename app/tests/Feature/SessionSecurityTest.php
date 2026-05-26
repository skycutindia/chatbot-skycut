<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function createOwner(): User
    {
        $org = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'is_active' => true,
        ]);

        return User::create([
            'organization_id' => $org->id,
            'name' => 'Test Owner',
            'email' => 'owner@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_session_expired_route_logs_out_user(): void
    {
        $user = $this->createOwner();
        Website::create([
            'organization_id' => $user->organization_id,
            'name' => 'SkyCut',
            'demo_slug' => config('chatbot.demo_website_slug', 'skycut'),
            'url' => 'https://example.test/demo/skycut',
            'domain' => 'example.test',
            'language' => 'en',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/auth/session-expired')
            ->assertRedirect(route('demo.show', config('chatbot.demo_website_slug', 'skycut')));

        $this->assertGuest();
    }

    public function test_logout_beacon_invalidates_session(): void
    {
        $user = $this->createOwner();

        $this->actingAs($user)
            ->post('/auth/logout-beacon')
            ->assertNoContent();

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_access_dashboard(): void
    {
        $user = $this->createOwner();
        Website::create([
            'organization_id' => $user->organization_id,
            'name' => 'SkyCut',
            'demo_slug' => config('chatbot.demo_website_slug', 'skycut'),
            'url' => 'https://example.test/demo/skycut',
            'domain' => 'example.test',
            'language' => 'en',
            'is_active' => true,
        ]);
        $user->update(['is_active' => false]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('demo.show', config('chatbot.demo_website_slug', 'skycut')));

        $this->assertGuest();
    }
}
