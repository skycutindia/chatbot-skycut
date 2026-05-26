<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AgentQuickReply;
use App\Models\Organization;
use App\Models\User;
use App\Services\AgentQuickReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentQuickReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeAgents(): array
    {
        $org = Organization::create([
            'name' => 'QR Org',
            'slug' => 'qr-org',
            'is_active' => true,
        ]);

        $agentA = User::create([
            'organization_id' => $org->id,
            'name' => 'Agent A',
            'email' => 'agenta@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $agentB = User::create([
            'organization_id' => $org->id,
            'name' => 'Agent B',
            'email' => 'agentb@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return [$org, $agentA, $agentB];
    }

    public function test_service_seeds_defaults_per_agent(): void
    {
        [, $agentA, $agentB] = $this->makeAgents();
        $service = app(AgentQuickReplyService::class);

        $a = $service->forInbox($agentA);
        $b = $service->forInbox($agentB);

        $this->assertCount(4, $a);
        $this->assertCount(4, $b);
        $this->assertNotSame($a->first()->id, $b->first()->id);
        $this->assertSame('Thanks for reaching out! How can I help you today?', $a->first()->body);
    }

    public function test_agent_can_store_and_destroy_own_quick_reply(): void
    {
        [, $agentA] = $this->makeAgents();
        app(AgentQuickReplyService::class)->forInbox($agentA);

        $response = $this->actingAs($agentA)
            ->postJson(route('inbox.quick-replies.store'), [
                'title' => 'Custom',
                'body' => 'This is my personal shortcut.',
            ]);

        $response->assertOk()->assertJsonPath('reply.body', 'This is my personal shortcut.');

        $reply = AgentQuickReply::query()->where('user_id', $agentA->id)->where('body', 'This is my personal shortcut.')->first();
        $this->assertNotNull($reply);

        $this->actingAs($agentA)
            ->deleteJson(route('inbox.quick-replies.destroy', $reply))
            ->assertOk();

        $this->assertDatabaseMissing('agent_quick_replies', ['id' => $reply->id]);
    }

    public function test_agent_can_update_own_quick_reply(): void
    {
        [, $agentA] = $this->makeAgents();
        app(AgentQuickReplyService::class)->forInbox($agentA);

        $reply = AgentQuickReply::query()->where('user_id', $agentA->id)->first();

        $this->actingAs($agentA)
            ->putJson(route('inbox.quick-replies.update', $reply), [
                'title' => 'Updated label',
                'body' => 'Updated body text.',
            ])
            ->assertOk()
            ->assertJsonPath('reply.body', 'Updated body text.');

        $this->assertSame('Updated body text.', $reply->fresh()->body);
    }

    public function test_agent_cannot_delete_another_agents_quick_reply(): void
    {
        [, $agentA, $agentB] = $this->makeAgents();
        app(AgentQuickReplyService::class)->forInbox($agentA);
        app(AgentQuickReplyService::class)->forInbox($agentB);

        $owned = AgentQuickReply::query()->where('user_id', $agentA->id)->first();

        $this->actingAs($agentB)
            ->deleteJson(route('inbox.quick-replies.destroy', $owned))
            ->assertForbidden();
    }
}
