<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function createLiveChatFixtures(): array
    {
        $org = Organization::create([
            'name' => 'Live Chat Org',
            'slug' => 'live-chat-org',
            'is_active' => true,
        ]);

        $owner = User::create([
            'organization_id' => $org->id,
            'name' => 'Inbox Owner',
            'email' => 'inbox@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Chat Site',
            'demo_slug' => 'chat-site',
            'url' => 'https://chat.test',
            'domain' => 'chat.test',
            'language' => 'en',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        $website->operatingHours()->update([
            'opens_at' => '00:00:00',
            'closes_at' => '23:59:59',
            'is_closed' => false,
        ]);

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v_test123',
            'visitor_name' => 'Jane Visitor',
            'visitor_email' => 'jane@example.test',
            'status' => 'awaiting_agent',
            'mode' => 'human',
            'priority' => 'high',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'visitor',
            'content' => 'I need help from a human',
            'source' => 'user',
        ]);

        return [$owner, $website, $conversation];
    }

    public function test_inbox_archive_and_queue_pages_load(): void
    {
        [$user] = $this->createLiveChatFixtures();

        $this->actingAs($user)->get('/inbox/archive')->assertOk()->assertSee('Archive');
        $this->actingAs($user)->get('/inbox/queue')->assertOk()->assertSee('Queue');
    }

    public function test_queue_take_chat_assigns_with_redirect(): void
    {
        [$user, , $conversation] = $this->createLiveChatFixtures();

        $target = route('inbox.index', ['conversation' => $conversation->id]);

        $this->actingAs($user)
            ->post(route('inbox.assign', $conversation), ['redirect' => $target])
            ->assertRedirect($target);

        $conversation->refresh();
        $this->assertSame($user->id, $conversation->assigned_user_id);
    }

    public function test_viewer_cannot_bulk_close_inbox(): void
    {
        [$user, , $conversation] = $this->createLiveChatFixtures();

        $viewer = User::create([
            'organization_id' => $user->organization_id,
            'name' => 'Viewer',
            'email' => 'viewer@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Viewer->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->postJson(route('inbox.bulk'), ['ids' => [$conversation->id], 'action' => 'close'])
            ->assertForbidden();
    }

    public function test_viewer_cannot_reply_via_inbox(): void
    {
        [$user, , $conversation] = $this->createLiveChatFixtures();

        $viewer = User::create([
            'organization_id' => $user->organization_id,
            'name' => 'Viewer',
            'email' => 'viewer2@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Viewer->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->postJson(route('inbox.reply', $conversation), ['content' => 'Should not send'])
            ->assertForbidden();
    }

    public function test_viewer_can_export_transcript_and_sees_readonly_inbox(): void
    {
        [$user, , $conversation] = $this->createLiveChatFixtures();
        $conversation->update(['status' => 'closed', 'closed_at' => now()]);

        $viewer = User::create([
            'organization_id' => $user->organization_id,
            'name' => 'Viewer',
            'email' => 'viewer3@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Viewer->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->get(route('inbox.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Read-only', false)
            ->assertSee('Export CSV', false)
            ->assertDontSee('id="lc-reply-form"', false);

        $this->actingAs($viewer)
            ->get(route('inbox.transcript.csv', $conversation))
            ->assertOk();
    }

    public function test_agent_can_assign_and_reply_via_inbox(): void
    {
        [$user, , $conversation] = $this->createLiveChatFixtures();

        $this->actingAs($user)
            ->post(route('inbox.assign', $conversation))
            ->assertRedirect();

        $conversation->refresh();
        $this->assertSame($user->id, $conversation->assigned_user_id);
        $this->assertSame('open', $conversation->status);

        $response = $this->actingAs($user)
            ->postJson(route('inbox.reply', $conversation), ['content' => 'Hello, how can I help?']);

        $response->assertOk()->assertJsonStructure(['message' => ['id', 'content', 'sender_type']]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'content' => 'Hello, how can I help?',
        ]);

        $conversation->refresh();
        $this->assertNotNull($conversation->first_response_at);
    }

    public function test_inbox_bulk_star_and_close(): void
    {
        [$user, , $conversation] = $this->createLiveChatFixtures();

        $this->actingAs($user)
            ->postJson(route('inbox.bulk'), [
                'ids' => [$conversation->id],
                'action' => 'star',
            ])
            ->assertOk()
            ->assertJson(['updated' => 1]);

        $conversation->refresh();
        $this->assertTrue($conversation->is_starred);

        $this->actingAs($user)
            ->postJson(route('inbox.bulk'), [
                'ids' => [$conversation->id],
                'action' => 'close',
            ])
            ->assertOk();

        $conversation->refresh();
        $this->assertSame('closed', $conversation->status);
    }

    public function test_widget_start_creates_conversation_with_required_contact_fields(): void
    {
        [, $website] = $this->createLiveChatFixtures();

        $website->configuration()->update([
            'welcome_message' => 'Hello! Thanks for reaching out — how can we help?',
        ]);

        $response = $this->postJson("/api/widget/{$website->bot_token}/start", [
            'visitor_id' => 'v_prechat_1',
            'visitor_name' => 'Alex Visitor',
            'visitor_phone' => '+15551234567',
            'visitor_email' => 'alex@example.test',
            'visitor_company' => 'Acme Inc',
        ]);

        $response->assertOk()
            ->assertJsonPath('visitor.name', 'Alex Visitor')
            ->assertJsonPath('visitor.phone', '+15551234567')
            ->assertJsonStructure(['conversation_id', 'greeting']);

        $conversation = Conversation::query()->where('visitor_id', 'v_prechat_1')->first();
        $this->assertNotNull($conversation);
        $this->assertSame('Alex Visitor', $conversation->visitor_name);
        $this->assertSame('+15551234567', $conversation->visitor_phone);
        $this->assertSame('alex@example.test', $conversation->visitor_email);
        $this->assertSame('Acme Inc', $conversation->visitor_company);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_type' => 'bot',
            'source' => 'greeting',
        ]);

        $this->assertDatabaseHas('leads', [
            'conversation_id' => $conversation->id,
            'name' => 'Alex Visitor',
        ]);
    }

    public function test_widget_start_requires_name_and_phone(): void
    {
        [, $website] = $this->createLiveChatFixtures();

        $this->postJson("/api/widget/{$website->bot_token}/start", [
            'visitor_id' => 'v_prechat_2',
            'visitor_name' => 'No Phone',
        ])->assertStatus(422);

        $this->assertNull(Conversation::query()->where('visitor_id', 'v_prechat_2')->first());
    }

    public function test_inbox_contact_update_requires_name_and_phone(): void
    {
        [$user, , $conversation] = $this->createLiveChatFixtures();

        $this->actingAs($user)
            ->postJson(route('inbox.contact', $conversation), [
                'visitor_name' => '',
                'visitor_phone' => '',
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('inbox.contact', $conversation), [
                'visitor_name' => 'Updated Name',
                'visitor_phone' => '+19998887777',
                'visitor_email' => 'updated@example.test',
                'visitor_company' => 'New Co',
            ])
            ->assertOk()
            ->assertJsonPath('contact.name', 'Updated Name')
            ->assertJsonPath('contact.phone', '+19998887777');

        $conversation->refresh();
        $this->assertSame('Updated Name', $conversation->visitor_name);
        $this->assertSame('+19998887777', $conversation->visitor_phone);
        $this->assertSame('New Co', $conversation->visitor_company);
    }

    public function test_widget_close_archives_conversation_for_dashboard(): void
    {
        [, $website, $conversation] = $this->createLiveChatFixtures();

        $conversation->update(['status' => 'open']);

        $this->postJson("/api/widget/{$website->bot_token}/close", [
            'visitor_id' => 'v_test123',
            'conversation_id' => $conversation->id,
        ])->assertOk()->assertJsonPath('status', 'closed');

        $conversation->refresh();
        $this->assertSame('closed', $conversation->status);
        $this->assertNotNull($conversation->closed_at);
    }

    public function test_widget_history_ignores_closed_conversations(): void
    {
        [, $website, $conversation] = $this->createLiveChatFixtures();

        $conversation->update(['status' => 'closed', 'closed_at' => now()]);

        $response = $this->getJson("/api/widget/{$website->bot_token}/history?visitor_id=v_test123&conversation_id={$conversation->id}");

        $response->assertOk()->assertJsonPath('messages', []);
    }

    public function test_awaiting_agent_chat_does_not_duplicate_handoff_notice(): void
    {
        [, $website, $conversation] = $this->createLiveChatFixtures();

        $conversation->update(['status' => 'awaiting_agent', 'mode' => 'human']);

        $first = $this->postJson("/api/widget/{$website->bot_token}/chat", [
            'visitor_id' => 'v_test123',
            'conversation_id' => $conversation->id,
            'message' => 'Still waiting for help',
        ])->assertOk()->assertJson(['handoff' => true]);

        $firstMessageId = $first->json('message.id');

        $second = $this->postJson("/api/widget/{$website->bot_token}/chat", [
            'visitor_id' => 'v_test123',
            'conversation_id' => $conversation->id,
            'message' => 'Anyone there?',
        ])->assertOk()->assertJson(['handoff' => true]);

        $this->assertSame($firstMessageId, $second->json('message.id'));
        $this->assertSame(1, $conversation->messages()->where('source', 'handoff_pending')->count());
    }

    public function test_widget_handoff_endpoint_escalates_conversation(): void
    {
        [, $website, $conversation] = $this->createLiveChatFixtures();

        $conversation->update(['status' => 'open', 'mode' => 'ai']);

        $this->postJson("/api/widget/{$website->bot_token}/handoff", [
            'visitor_id' => 'v_test123',
            'conversation_id' => $conversation->id,
            'visitor_name' => 'Jane Visitor',
        ])->assertOk()->assertJson(['handoff' => true]);

        $conversation->refresh();
        $this->assertSame('awaiting_agent', $conversation->status);
        $this->assertSame('human', $conversation->mode);
    }

    public function test_widget_chat_keyword_triggers_handoff(): void
    {
        [, $website] = $this->createLiveChatFixtures();

        $response = $this->postJson("/api/widget/{$website->bot_token}/chat", [
            'message' => 'I want to talk to a human agent please',
            'visitor_id' => 'v_new456',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('handoff') ?? false);

        $conversation = Conversation::query()->where('visitor_id', 'v_new456')->first();
        $this->assertNotNull($conversation);
        $this->assertSame('awaiting_agent', $conversation->status);
    }

    public function test_inbox_meta_toggles_star_from_form_values(): void
    {
        [$user, , $conversation] = $this->createLiveChatFixtures();

        $this->actingAs($user)
            ->post(route('inbox.meta', $conversation), ['is_starred' => '1'])
            ->assertRedirect();

        $conversation->refresh();
        $this->assertTrue($conversation->is_starred);

        $this->actingAs($user)
            ->post(route('inbox.meta', $conversation), ['is_starred' => '0'])
            ->assertRedirect();

        $conversation->refresh();
        $this->assertFalse($conversation->is_starred);
    }

    public function test_live_chat_analytics_page_loads(): void
    {
        [$user] = $this->createLiveChatFixtures();

        $this->actingAs($user)
            ->get('/live-chat/analytics')
            ->assertOk()
            ->assertSee('Chat Analytics');
    }

    public function test_agent_can_upload_attachment(): void
    {
        [$user, , $conversation] = $this->createLiveChatFixtures();

        $file = \Illuminate\Http\UploadedFile::fake()->create('screenshot.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('inbox.attachments.upload', $conversation), ['file' => $file]);

        $response->assertOk()->assertJsonStructure(['message' => ['id', 'attachments']]);

        $this->assertDatabaseHas('file_attachments', [
            'original_name' => 'screenshot.pdf',
        ]);
    }

    public function test_departments_crud_and_inbox_assignment(): void
    {
        [$user, , $conversation] = $this->createLiveChatFixtures();

        $this->actingAs($user)
            ->post(route('departments.store'), [
                'name' => 'Support',
                'description' => 'General support team',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $department = \App\Models\Department::where('name', 'Support')->first();
        $this->assertNotNull($department);

        $this->actingAs($user)
            ->get(route('departments.index'))
            ->assertOk()
            ->assertSee('Support');

        $this->actingAs($user)
            ->post(route('inbox.meta', $conversation), ['department_id' => $department->id])
            ->assertRedirect();

        $conversation->refresh();
        $this->assertSame($department->id, $conversation->department_id);

        $this->actingAs($user)
            ->get(route('inbox.index', ['department_id' => $department->id, 'conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Support');
    }

    public function test_inbox_compose_includes_emoji_picker(): void
    {
        [$user, , $conversation] = $this->createLiveChatFixtures();

        $this->actingAs($user)
            ->get(route('inbox.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('id="lc-emoji-btn"', false)
            ->assertSee('id="lc-emoji-panel"', false)
            ->assertSee('emoji-picker.css', false);
    }

    public function test_keyword_automation_adds_tag_on_visitor_message(): void
    {
        [$user, $website, $conversation] = $this->createLiveChatFixtures();

        \App\Models\ChatAutomationRule::create([
            'organization_id' => $user->organization_id,
            'name' => 'Billing tag',
            'trigger_type' => 'keyword',
            'trigger_config' => ['keywords' => ['billing']],
            'action_type' => 'add_tag',
            'action_config' => ['tag' => 'billing'],
            'priority' => 10,
            'is_active' => true,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'visitor',
            'content' => 'I have a billing question',
            'source' => 'user',
        ]);

        $conversation->refresh();
        $this->assertContains('billing', $conversation->tags ?? []);
    }

    public function test_inactive_automation_closes_stale_conversations(): void
    {
        [$user, $website, $conversation] = $this->createLiveChatFixtures();

        \App\Models\ChatAutomationRule::create([
            'organization_id' => $user->organization_id,
            'name' => 'Auto close idle',
            'trigger_type' => 'inactive',
            'trigger_config' => ['minutes' => 30],
            'action_type' => 'close',
            'action_config' => [],
            'priority' => 0,
            'is_active' => true,
        ]);

        $conversation->update([
            'status' => 'open',
            'last_message_at' => now()->subMinutes(45),
        ]);

        $this->artisan('chat:close-inactive')->assertSuccessful();

        $conversation->refresh();
        $this->assertSame('closed', $conversation->status);
    }

    public function test_automation_rules_page_loads(): void
    {
        [$user] = $this->createLiveChatFixtures();

        $this->actingAs($user)
            ->get(route('automation-rules.index'))
            ->assertOk()
            ->assertSee('Automation rules');
    }

    public function test_admin_can_create_automation_rule(): void
    {
        [$user] = $this->createLiveChatFixtures();

        $this->actingAs($user)
            ->post(route('automation-rules.store'), [
                'name' => 'VIP keyword tag',
                'trigger_type' => 'keyword',
                'keywords' => 'vip, premium',
                'action_type' => 'add_tag',
                'tag' => 'vip',
                'priority' => 5,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('chat_automation_rules', [
            'organization_id' => $user->organization_id,
            'name' => 'VIP keyword tag',
            'trigger_type' => 'keyword',
            'action_type' => 'add_tag',
        ]);
    }
}
