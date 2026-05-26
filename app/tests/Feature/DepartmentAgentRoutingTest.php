<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use App\Services\AgentAssignmentService;
use App\Services\LiveHandoffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentAgentRoutingTest extends TestCase
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
            'name' => 'Routing Org',
            'slug' => 'routing-org',
            'is_active' => true,
        ]);

        $manager = User::create([
            'organization_id' => $org->id,
            'name' => 'Manager',
            'email' => 'manager@routing.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Manager->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $supportAgent = User::create([
            'organization_id' => $org->id,
            'name' => 'Support Agent',
            'email' => 'support@routing.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $salesAgent = User::create([
            'organization_id' => $org->id,
            'name' => 'Sales Agent',
            'email' => 'sales@routing.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Routing Site',
            'demo_slug' => 'routing-site',
            'url' => 'https://routing.test',
            'domain' => 'routing.test',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        $website->operatingHours()->update([
            'opens_at' => '00:00:00',
            'closes_at' => '23:59:59',
            'is_closed' => false,
        ]);

        $support = Department::create([
            'organization_id' => $org->id,
            'name' => 'Support',
            'slug' => 'support',
            'is_active' => true,
        ]);

        $sales = Department::create([
            'organization_id' => $org->id,
            'name' => 'Sales',
            'slug' => 'sales',
            'is_active' => true,
        ]);

        $support->agents()->sync([$supportAgent->id]);
        $sales->agents()->sync([$salesAgent->id]);

        return [$manager, $supportAgent, $salesAgent, $website, $support, $sales];
    }

    public function test_manager_can_sync_department_agents(): void
    {
        [$manager, $supportAgent, $salesAgent, , $support] = $this->fixtures();

        $this->actingAs($manager)
            ->put(route('departments.agents.sync', $support), [
                'user_ids' => [$supportAgent->id, $salesAgent->id],
            ])
            ->assertRedirect();

        $this->assertCount(2, $support->fresh()->agents);
    }

    public function test_handoff_assigns_agent_from_conversation_department(): void
    {
        [, $supportAgent, , $website, $support] = $this->fixtures();

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v_route1',
            'visitor_name' => 'Pat Visitor',
            'status' => 'open',
            'mode' => 'ai',
            'department_id' => $support->id,
            'last_message_at' => now(),
        ]);

        app(LiveHandoffService::class)->initiate($website, $conversation, 'visitor_request');

        $conversation->refresh();
        $this->assertSame($supportAgent->id, $conversation->assigned_user_id);
    }

    public function test_handoff_does_not_assign_agent_outside_department(): void
    {
        [, $supportAgent, $salesAgent, $website, $support] = $this->fixtures();

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v_route2',
            'visitor_name' => 'Pat Visitor',
            'status' => 'open',
            'mode' => 'ai',
            'department_id' => $support->id,
            'last_message_at' => now(),
        ]);

        $support->agents()->sync([]);

        app(LiveHandoffService::class)->initiate($website, $conversation, 'visitor_request');

        $conversation->refresh();
        $this->assertNull($conversation->assigned_user_id);

        $support->agents()->sync([$supportAgent->id]);
        $assignment = app(AgentAssignmentService::class)->assignLeastBusy($website, $support->id);
        $this->assertSame($supportAgent->id, $assignment?->id);
        $this->assertNotSame($salesAgent->id, $assignment?->id);
    }

    public function test_assign_least_busy_prefers_lowest_open_chat_count_in_department(): void
    {
        [, $supportAgent, , $website, $support] = $this->fixtures();

        $busyAgent = User::create([
            'organization_id' => $website->organization_id,
            'name' => 'Busy Support',
            'email' => 'busy@routing.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $support->agents()->sync([$supportAgent->id, $busyAgent->id]);

        Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v_busy',
            'assigned_user_id' => $busyAgent->id,
            'status' => 'open',
            'mode' => 'human',
            'department_id' => $support->id,
            'last_message_at' => now(),
        ]);

        $assigned = app(AgentAssignmentService::class)->assignLeastBusy($website, $support->id);

        $this->assertSame($supportAgent->id, $assigned?->id);
    }
}
