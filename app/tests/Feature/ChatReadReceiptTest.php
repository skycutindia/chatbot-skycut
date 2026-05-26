<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use App\Services\ChatReadReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatReadReceiptTest extends TestCase
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
            'name' => 'Receipt Org',
            'slug' => 'receipt-org',
            'is_active' => true,
        ]);

        $agent = User::create([
            'organization_id' => $org->id,
            'name' => 'Receipt Agent',
            'email' => 'agent@receipt.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Receipt Site',
            'demo_slug' => 'receipt-site',
            'url' => 'https://receipt.test',
            'domain' => 'receipt.test',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v_receipt',
            'visitor_name' => 'Visitor',
            'status' => 'open',
            'mode' => 'human',
            'assigned_user_id' => $agent->id,
            'last_message_at' => now(),
        ]);

        $visitorMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'visitor',
            'content' => 'Need help',
            'source' => 'user',
        ]);

        $agentMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
            'content' => 'How can I help?',
            'source' => 'live_agent',
        ]);

        return [$agent, $website, $conversation, $visitorMessage, $agentMessage];
    }

    public function test_widget_poll_marks_agent_message_delivered(): void
    {
        [, $website, $conversation, , $agentMessage] = $this->fixtures();

        $this->getJson("/api/widget/{$website->bot_token}/poll?visitor_id=v_receipt&conversation_id={$conversation->id}")
            ->assertOk();

        $agentMessage->refresh();
        $this->assertNotNull($agentMessage->delivered_at);
        $this->assertNull($agentMessage->read_at);
    }

    public function test_widget_read_marks_agent_message_read(): void
    {
        [, $website, $conversation, , $agentMessage] = $this->fixtures();

        $this->postJson("/api/widget/{$website->bot_token}/read", [
            'visitor_id' => 'v_receipt',
            'conversation_id' => $conversation->id,
            'message_ids' => [$agentMessage->id],
        ])->assertOk();

        $agentMessage->refresh();
        $this->assertNotNull($agentMessage->delivered_at);
        $this->assertNotNull($agentMessage->read_at);
    }

    public function test_agent_viewing_inbox_marks_visitor_message_read(): void
    {
        [$agent, , $conversation, $visitorMessage] = $this->fixtures();

        $this->actingAs($agent)
            ->get(route('inbox.index', ['conversation' => $conversation->id]))
            ->assertOk();

        $visitorMessage->refresh();
        $this->assertNotNull($visitorMessage->delivered_at);
        $this->assertNotNull($visitorMessage->read_at);
    }

    public function test_receipt_status_helper(): void
    {
        [, , , , $agentMessage] = $this->fixtures();
        $service = app(ChatReadReceiptService::class);

        $this->assertSame('sent', $service->receiptStatus($agentMessage));

        $agentMessage->update(['delivered_at' => now()]);
        $this->assertSame('delivered', $service->receiptStatus($agentMessage->fresh()));

        $agentMessage->update(['read_at' => now()]);
        $this->assertSame('read', $service->receiptStatus($agentMessage->fresh()));
    }
}
