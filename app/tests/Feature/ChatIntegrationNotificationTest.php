<?php

namespace Tests\Feature;

use App\Events\ConversationAwaitingAgent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\NotificationLog;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use App\Enums\UserRole;
use App\Services\ChatIntegrationNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatIntegrationNotificationTest extends TestCase
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
            'name' => 'Notify Org',
            'slug' => 'notify-org',
            'is_active' => true,
            'settings' => [
                'slack_webhook_url' => 'https://hooks.slack.com/services/T00/B00/xx',
                'teams_webhook_url' => 'https://outlook.office.com/webhook/abc',
                'notify_slack_handoff' => true,
                'notify_teams_handoff' => true,
                'notify_slack_new_message' => true,
                'notify_teams_new_message' => false,
            ],
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'owner@notify.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Notify Site',
            'demo_slug' => 'notify-site',
            'url' => 'https://notify.test',
            'domain' => 'notify.test',
            'is_active' => true,
        ]);

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v_notify',
            'visitor_name' => 'Sam Visitor',
            'status' => 'awaiting_agent',
            'mode' => 'human',
            'last_message_at' => now(),
        ]);

        return [$org, $user, $website, $conversation];
    }

    public function test_handoff_sends_slack_and_teams_webhooks(): void
    {
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200),
            'outlook.office.com/*' => Http::response('ok', 200),
        ]);

        [, , , $conversation] = $this->fixtures();

        app(ChatIntegrationNotificationService::class)->notifyHandoff($conversation, 'visitor_request');

        Http::assertSentCount(2);

        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'slack',
            'event_type' => 'handoff',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'teams',
            'event_type' => 'handoff',
            'status' => 'sent',
        ]);
    }

    public function test_new_message_in_queue_notifies_slack_when_enabled(): void
    {
        Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);

        [, , , $conversation] = $this->fixtures();

        $message = new Message([
            'conversation_id' => $conversation->id,
            'sender_type' => 'visitor',
            'content' => 'Anyone there?',
            'source' => 'user',
        ]);

        app(ChatIntegrationNotificationService::class)->notifyNewVisitorMessage($conversation, $message);

        Http::assertSentCount(1);
        $this->assertEquals(1, NotificationLog::where('event_type', 'new_message')->where('channel', 'slack')->count());
    }

    public function test_organization_settings_save_webhook_urls(): void
    {
        [, $user] = $this->fixtures();

        $this->actingAs($user)
            ->put(route('settings.organization.update'), [
                'name' => 'Notify Org',
                'timezone' => 'UTC',
                'slack_webhook_url' => 'https://hooks.slack.com/services/T00/B00/new',
                'teams_webhook_url' => 'https://outlook.office.com/webhook/new',
                'notify_slack_handoff' => '1',
                'notify_teams_handoff' => '1',
            ])
            ->assertRedirect();

        $user->organization->refresh();
        $this->assertSame('https://hooks.slack.com/services/T00/B00/new', $user->organization->settings['slack_webhook_url']);
        $this->assertTrue($user->organization->settings['notify_slack_handoff']);
    }

    public function test_awaiting_agent_event_triggers_integration_listener(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        [, , , $conversation] = $this->fixtures();

        event(new ConversationAwaitingAgent($conversation, 'low_confidence'));

        $this->assertGreaterThanOrEqual(1, NotificationLog::where('event_type', 'handoff')->count());
    }
}
