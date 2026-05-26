<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Organization;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetCsatTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_event_persists_conversation_rating(): void
    {
        $org = Organization::create([
            'name' => 'CSAT Org',
            'slug' => 'csat-org',
            'is_active' => true,
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'CSAT Site',
            'demo_slug' => 'csat-site',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v-csat',
            'status' => 'open',
            'mode' => 'ai',
            'channel' => 'widget',
            'last_message_at' => now(),
        ]);

        $this->postJson("/api/widget/{$website->bot_token}/events", [
            'event_type' => 'conversation_rating',
            'visitor_id' => 'v-csat',
            'conversation_id' => $conversation->id,
            'payload' => ['score' => 4, 'comment' => 'Helpful bot'],
        ])->assertOk();

        $this->assertDatabaseHas('conversation_ratings', [
            'conversation_id' => $conversation->id,
            'score' => 4,
            'comment' => 'Helpful bot',
        ]);
    }

    public function test_widget_config_includes_csat_module_by_default(): void
    {
        $org = Organization::create([
            'name' => 'Mod Org',
            'slug' => 'mod-csat-org',
            'is_active' => true,
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Mod Site',
            'demo_slug' => 'mod-csat-site',
            'is_active' => true,
        ]);

        $this->getJson("/api/widget/{$website->bot_token}/config")
            ->assertOk()
            ->assertJsonPath('modules.csat_survey', true);
    }
}
