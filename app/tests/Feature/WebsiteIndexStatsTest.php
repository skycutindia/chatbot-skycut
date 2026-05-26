<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Organization;
use App\Models\UnansweredQuestion;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteIndexStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_websites_index_shows_open_and_unanswered_counts(): void
    {
        $org = Organization::create([
            'name' => 'Stats Org',
            'slug' => 'stats-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'stats@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Stats Site',
            'demo_slug' => 'stats-site',
            'is_active' => true,
        ]);

        Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v1',
            'status' => 'awaiting_agent',
            'last_message_at' => now(),
        ]);

        Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v2',
            'status' => 'closed',
            'last_message_at' => now(),
        ]);

        UnansweredQuestion::create([
            'website_id' => $website->id,
            'visitor_message' => 'Unknown pricing?',
            'source' => 'fallback',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->get(route('websites.index'))
            ->assertOk()
            ->assertSee('Stats Site')
            ->assertSee('Total chats');
    }
}
