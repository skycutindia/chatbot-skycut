<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\ConversationRating;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxConversationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_export_csv_respects_filters(): void
    {
        $org = Organization::create([
            'name' => 'Export Org',
            'slug' => 'export-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Agent',
            'email' => 'export-agent@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Export Site',
            'demo_slug' => 'export-site',
            'is_active' => true,
        ]);

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v-export',
            'visitor_name' => 'Jane Doe',
            'visitor_email' => 'jane@example.test',
            'status' => 'open',
            'mode' => 'ai',
            'channel' => 'widget',
            'last_message_at' => now(),
        ]);

        ConversationRating::create([
            'conversation_id' => $conversation->id,
            'score' => 5,
            'comment' => 'Great help',
        ]);

        $response = $this->actingAs($user)
            ->get(route('inbox.export', ['website_id' => $website->id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $body = $response->streamedContent();
        $this->assertStringContainsString('Jane Doe', $body);
        $this->assertStringContainsString('Export Site', $body);
        $this->assertStringContainsString('5', $body);
        $this->assertStringContainsString('Great help', $body);
    }
}
