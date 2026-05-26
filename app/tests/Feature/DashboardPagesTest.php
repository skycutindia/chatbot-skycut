<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\ConversationRating;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function createOwnerWithWebsite(): array
    {
        $org = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Test Owner',
            'email' => 'owner@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Test Site',
            'demo_slug' => 'test-site',
            'url' => 'https://example.test',
            'domain' => 'example.test',
            'language' => 'en',
            'is_active' => true,
        ]);

        return [$user, $website];
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_loads_for_verified_owner(): void
    {
        [$user] = $this->createOwnerWithWebsite();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Welcome back');
    }

    public function test_dashboard_shows_today_analytics_slice(): void
    {
        [$user, $website] = $this->createOwnerWithWebsite();

        Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v_today',
            'status' => 'open',
            'mode' => 'ai',
            'last_message_at' => now(),
        ]);

        Lead::create([
            'organization_id' => $user->organization_id,
            'website_id' => $website->id,
            'name' => 'Today Lead',
            'email' => 'lead@today.test',
            'source_url' => 'https://example.test',
        ]);

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v_csat',
            'status' => 'closed',
            'mode' => 'ai',
            'last_message_at' => now(),
        ]);

        ConversationRating::create([
            'conversation_id' => $conversation->id,
            'score' => 5,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Chats today')
            ->assertSee('Leads today')
            ->assertSee('Avg CSAT today');
    }

    public function test_live_inbox_loads_without_server_error(): void
    {
        [$user] = $this->createOwnerWithWebsite();

        $this->actingAs($user)
            ->get('/inbox')
            ->assertOk()
            ->assertSee('Live Inbox');
    }

    public function test_demo_storefront_renders_without_blade_errors(): void
    {
        [, $website] = $this->createOwnerWithWebsite();

        $this->get('/demo/'.$website->demo_slug)
            ->assertOk()
            ->assertSee('AI-Powered Customer Support');
    }

    public function test_login_page_loads(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to dashboard');
    }
}
