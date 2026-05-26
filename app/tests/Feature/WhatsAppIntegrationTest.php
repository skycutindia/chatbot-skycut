<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Organization;
use App\Models\QaPair;
use App\Models\User;
use App\Models\Website;
use App\Models\WhatsappChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppIntegrationTest extends TestCase
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
            'name' => 'WA Org',
            'slug' => 'wa-org',
            'is_active' => true,
        ]);

        $owner = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'owner@wa.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'WA Site',
            'demo_slug' => 'wa-site',
            'url' => 'https://wa.test',
            'domain' => 'wa.test',
            'language' => 'en',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        $website->configuration()->update([
            'ai_enabled' => false,
            'offline_message' => 'We will reply shortly.',
        ]);

        QaPair::create([
            'website_id' => $website->id,
            'question' => 'hours',
            'answer' => 'We are open 9–5 weekdays.',
            'is_active' => true,
            'is_published' => true,
            'priority' => 1,
        ]);

        $channel = WhatsappChannel::create([
            'organization_id' => $org->id,
            'website_id' => $website->id,
            'phone_number_id' => '1234567890',
            'display_phone' => '+15550100',
            'access_token' => 'test-token',
            'verify_token' => 'verify-me-123',
            'is_active' => true,
        ]);

        return [$org, $owner, $website, $channel];
    }

    public function test_settings_page_loads_for_owner(): void
    {
        [, $owner] = $this->fixtures();

        $this->actingAs($owner)
            ->get(route('settings.whatsapp.edit'))
            ->assertOk()
            ->assertSee('WhatsApp Business')
            ->assertSee('verify-me-123');
    }

    public function test_webhook_verification_returns_challenge(): void
    {
        [$org] = $this->fixtures();

        $this->get('/api/webhooks/whatsapp/'.$org->slug.'?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'verify-me-123',
            'hub_challenge' => 'challenge-token-abc',
        ]))->assertOk()->assertSee('challenge-token-abc');
    }

    public function test_inbound_whatsapp_creates_conversation_and_sends_reply(): void
    {
        [$org, , $website, $channel] = $this->fixtures();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200),
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => $channel->phone_number_id],
                        'contacts' => [['profile' => ['name' => 'Alice WA']]],
                        'messages' => [[
                            'from' => '15551234567',
                            'id' => 'wamid.in',
                            'type' => 'text',
                            'text' => ['body' => 'What are your hours?'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/api/webhooks/whatsapp/'.$org->slug, $payload)->assertNoContent();

        $conversation = Conversation::query()
            ->where('website_id', $website->id)
            ->where('channel', 'whatsapp')
            ->where('channel_contact_id', '15551234567')
            ->first();

        $this->assertNotNull($conversation);
        $this->assertSame('Alice WA', $conversation->visitor_name);
        $this->assertSame(2, $conversation->messages()->count());

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/messages')
                && $request['to'] === '15551234567'
                && str_contains($request['text']['body'], '9–5');
        });
    }

    public function test_agent_inbox_reply_sends_whatsapp_message(): void
    {
        [$org, $owner, $website, $channel] = $this->fixtures();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.agent']]], 200),
        ]);

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'wa:15551234567',
            'visitor_name' => 'Alice WA',
            'visitor_phone' => '+15551234567',
            'channel' => 'whatsapp',
            'channel_contact_id' => '15551234567',
            'status' => 'open',
            'mode' => 'human',
            'assigned_user_id' => $owner->id,
            'last_message_at' => now(),
        ]);

        $this->actingAs($owner)
            ->postJson(route('inbox.reply', $conversation), ['content' => 'Thanks for waiting — how can I help?'])
            ->assertOk();

        Http::assertSent(function ($request) {
            return $request['to'] === '15551234567'
                && str_contains($request['text']['body'], 'Thanks for waiting');
        });
    }
}
