<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\ConversationNote;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTranscriptExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function fixtures(): array
    {
        $org = Organization::create([
            'name' => 'Transcript Org',
            'slug' => 'transcript-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Export Agent',
            'email' => 'export@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Export Site',
            'demo_slug' => 'export-site',
            'url' => 'https://export.test',
            'domain' => 'export.test',
            'is_active' => true,
        ]);

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v_export',
            'visitor_name' => 'Alex Visitor',
            'visitor_email' => 'alex@example.test',
            'status' => 'closed',
            'mode' => 'human',
            'assigned_user_id' => $user->id,
            'last_message_at' => now(),
            'closed_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'visitor',
            'content' => 'Hello, I need pricing info.',
            'source' => 'user',
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'content' => 'Happy to help with pricing.',
            'source' => 'agent',
        ]);

        ConversationNote::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => 'Follow up next week.',
        ]);

        return [$user, $conversation];
    }

    public function test_agent_can_export_transcript_csv(): void
    {
        [$user, $conversation] = $this->fixtures();

        $response = $this->actingAs($user)
            ->get(route('inbox.transcript.csv', $conversation));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Alex Visitor', $response->streamedContent());
        $this->assertStringContainsString('pricing info', $response->streamedContent());
    }

    public function test_agent_can_export_transcript_txt(): void
    {
        [$user, $conversation] = $this->fixtures();

        $response = $this->actingAs($user)
            ->get(route('inbox.transcript.txt', $conversation));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/plain; charset=UTF-8');
        $response->assertSee('Chat transcript');
        $response->assertSee('Follow up next week.');
    }

    public function test_agent_can_export_transcript_pdf(): void
    {
        [$user, $conversation] = $this->fixtures();

        $response = $this->actingAs($user)
            ->get(route('inbox.transcript.pdf', $conversation));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_other_organization_cannot_export_transcript(): void
    {
        [, $conversation] = $this->fixtures();

        $otherOrg = Organization::create([
            'name' => 'Other Org',
            'slug' => 'other-org',
            'is_active' => true,
        ]);

        $intruder = User::create([
            'organization_id' => $otherOrg->id,
            'name' => 'Intruder',
            'email' => 'intruder@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($intruder)
            ->get(route('inbox.transcript.csv', $conversation))
            ->assertForbidden();
    }
}
