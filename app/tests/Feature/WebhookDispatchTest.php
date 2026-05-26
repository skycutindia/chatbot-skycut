<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Organization;
use App\Models\Webhook;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function websiteWithWebhook(array $events, bool $active = true): Website
    {
        $org = Organization::create([
            'name' => 'Webhook Org',
            'slug' => 'webhook-org',
            'is_active' => true,
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Hook Site',
            'demo_slug' => 'hook-site',
            'url' => 'https://hook.test',
            'domain' => 'hook.test',
            'language' => 'en',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        $website->operatingHours()->update([
            'opens_at' => '00:00:00',
            'closes_at' => '23:59:59',
            'is_closed' => false,
        ]);

        Webhook::create([
            'website_id' => $website->id,
            'name' => 'Test hook',
            'url' => 'https://hooks.example.test/receive',
            'events' => $events,
            'secret' => 'whsec_test',
            'is_active' => $active,
        ]);

        return $website;
    }

    public function test_chat_started_webhook_dispatched_on_widget_start(): void
    {
        $website = $this->websiteWithWebhook(['chat.started']);

        Http::fake(['hooks.example.test/*' => Http::response('ok', 200)]);

        $this->postJson("/api/widget/{$website->bot_token}/start", [
            'visitor_id' => 'v_hook_start',
            'visitor_name' => 'Pat Visitor',
            'visitor_phone' => '+15550001111',
            'visitor_email' => 'pat@example.test',
        ])->assertOk();

        Http::assertSent(function ($request) use ($website) {
            $body = $request->data();

            return $request->url() === 'https://hooks.example.test/receive'
                && ($body['event'] ?? null) === 'chat.started'
                && ($body['website_id'] ?? null) === $website->id
                && isset($body['payload']['conversation_id']);
        });
    }

    public function test_lead_created_webhook_dispatched_on_new_lead(): void
    {
        $website = $this->websiteWithWebhook(['lead.created']);

        Http::fake(['hooks.example.test/*' => Http::response('ok', 200)]);

        $this->postJson("/api/widget/{$website->bot_token}/start", [
            'visitor_id' => 'v_hook_lead',
            'visitor_name' => 'Lead Visitor',
            'visitor_phone' => '+15550002222',
            'visitor_email' => 'lead@example.test',
        ])->assertOk();

        Http::assertSent(function ($request) {
            $body = $request->data();

            return ($body['event'] ?? null) === 'lead.created'
                && isset($body['payload']['lead_id']);
        });
    }

    public function test_chat_closed_webhook_dispatched_on_widget_close(): void
    {
        $website = $this->websiteWithWebhook(['chat.closed']);

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v_hook_close',
            'status' => 'open',
            'mode' => 'ai',
            'last_message_at' => now(),
        ]);

        Http::fake(['hooks.example.test/*' => Http::response('ok', 200)]);

        $this->postJson("/api/widget/{$website->bot_token}/close", [
            'visitor_id' => 'v_hook_close',
            'conversation_id' => $conversation->id,
        ])->assertOk();

        Http::assertSent(function ($request) use ($conversation) {
            $body = $request->data();

            return ($body['event'] ?? null) === 'chat.closed'
                && ($body['payload']['conversation_id'] ?? null) === $conversation->id;
        });
    }

    public function test_webhook_not_sent_when_event_not_subscribed(): void
    {
        $website = $this->websiteWithWebhook(['chat.closed']);

        Http::fake(['hooks.example.test/*' => Http::response('ok', 200)]);

        $this->postJson("/api/widget/{$website->bot_token}/start", [
            'visitor_id' => 'v_no_start_hook',
            'visitor_name' => 'Skip Start',
            'visitor_phone' => '+15550003333',
        ])->assertOk();

        Http::assertNothingSent();
    }

    public function test_inactive_webhook_is_not_dispatched(): void
    {
        $website = $this->websiteWithWebhook(['chat.started'], false);

        Http::fake(['hooks.example.test/*' => Http::response('ok', 200)]);

        $this->postJson("/api/widget/{$website->bot_token}/start", [
            'visitor_id' => 'v_inactive_hook',
            'visitor_name' => 'Inactive Hook',
            'visitor_phone' => '+15550004444',
        ])->assertOk();

        Http::assertNothingSent();
    }
}
