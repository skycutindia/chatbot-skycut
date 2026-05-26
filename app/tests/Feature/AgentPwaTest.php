<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AgentPushSubscription;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentPwaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function agentUser(): User
    {
        $org = Organization::create([
            'name' => 'PWA Org',
            'slug' => 'pwa-org',
            'is_active' => true,
        ]);

        return User::create([
            'organization_id' => $org->id,
            'name' => 'Agent',
            'email' => 'agent@pwa.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_manifest_returns_web_app_manifest(): void
    {
        $response = $this->get(route('agent.pwa.manifest'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');

        $response->assertJsonFragment([
            'name' => config('chatbot.pwa.name'),
            'short_name' => config('chatbot.pwa.short_name'),
            'display' => 'standalone',
        ]);
    }

    public function test_icon_returns_svg(): void
    {
        $response = $this->get(route('agent.pwa.icon', ['size' => 192]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertSee('<svg', false);
    }

    public function test_agent_can_subscribe_to_push(): void
    {
        $user = $this->agentUser();

        $payload = [
            'endpoint' => 'https://push.example.test/subscription/abc',
            'keys' => [
                'p256dh' => 'test-p256dh-key',
                'auth' => 'test-auth-token',
            ],
        ];

        $this->actingAs($user)
            ->postJson(route('inbox.push.subscribe'), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('agent_push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => $payload['endpoint'],
            'public_key' => 'test-p256dh-key',
            'auth_token' => 'test-auth-token',
        ]);
    }

    public function test_agent_can_unsubscribe_from_push(): void
    {
        $user = $this->agentUser();
        $endpoint = 'https://push.example.test/subscription/xyz';

        AgentPushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => $endpoint,
            'public_key' => 'key',
            'auth_token' => 'token',
        ]);

        $this->actingAs($user)
            ->postJson(route('inbox.push.unsubscribe'), ['endpoint' => $endpoint])
            ->assertOk();

        $this->assertDatabaseMissing('agent_push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => $endpoint,
        ]);
    }

    public function test_viewer_cannot_subscribe_to_push(): void
    {
        $org = Organization::create([
            'name' => 'Viewer Org',
            'slug' => 'viewer-org',
            'is_active' => true,
        ]);

        $viewer = User::create([
            'organization_id' => $org->id,
            'name' => 'Viewer',
            'email' => 'viewer@pwa.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Viewer->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->postJson(route('inbox.push.subscribe'), [
                'endpoint' => 'https://push.example.test/subscription/viewer',
            ])
            ->assertForbidden();
    }

    public function test_inbox_includes_pwa_install_banner(): void
    {
        $user = $this->agentUser();

        $this->actingAs($user)
            ->get(route('inbox.index'))
            ->assertOk()
            ->assertSee('id="agent-pwa-install"', false)
            ->assertSee('Enable notifications', false);
    }
}
