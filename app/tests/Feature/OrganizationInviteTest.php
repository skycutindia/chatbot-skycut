<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Mail\OrganizationInviteMail;
use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrganizationInviteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    /** @return array{Organization, User} */
    protected function orgWithAdmin(): array
    {
        $org = Organization::create([
            'name' => 'Invite Org',
            'slug' => 'invite-org',
            'is_active' => true,
        ]);

        $admin = User::create([
            'organization_id' => $org->id,
            'name' => 'Admin',
            'email' => 'invite-admin@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Admin->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return [$org, $admin];
    }

    public function test_admin_can_create_pending_invite_from_organization_settings(): void
    {
        Mail::fake();

        [$org, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin)
            ->post(route('settings.organization.invites.store'), [
                'email' => 'new-agent@test.local',
                'role' => UserRole::Agent->value,
            ])
            ->assertRedirect();

        $invite = OrganizationInvite::query()->where('organization_id', $org->id)->first();
        $this->assertNotNull($invite);
        $this->assertSame('new-agent@test.local', $invite->email);
        $this->assertNull($invite->accepted_at);

        $this->actingAs($admin)
            ->get(route('settings.organization.edit'))
            ->assertOk()
            ->assertSee(route('team.invite.show', $invite->token), false);

        Mail::assertSent(OrganizationInviteMail::class, function (OrganizationInviteMail $mail) use ($invite) {
            return $mail->hasTo('new-agent@test.local')
                && $mail->invite->is($invite);
        });
    }

    public function test_guest_can_accept_invite_and_join_organization(): void
    {
        [$org] = $this->orgWithAdmin();

        $invite = OrganizationInvite::create([
            'organization_id' => $org->id,
            'email' => 'joined@test.local',
            'role' => UserRole::Agent->value,
            'token' => OrganizationInvite::generateToken(),
            'expires_at' => now()->addDay(),
        ]);

        $this->get(route('team.invite.show', $invite->token))
            ->assertOk()
            ->assertSee('Join Invite Org');

        $this->post(route('team.invite.accept', $invite->token), [
            'name' => 'Joined Agent',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ])
            ->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'joined@test.local')->first();
        $this->assertNotNull($user);
        $this->assertSame($org->id, $user->organization_id);
        $this->assertNotNull($invite->fresh()->accepted_at);
    }

    public function test_agent_cannot_send_invites(): void
    {
        [$org] = $this->orgWithAdmin();

        $agent = User::create([
            'organization_id' => $org->id,
            'name' => 'Agent',
            'email' => 'invite-agent@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($agent)
            ->post(route('settings.organization.invites.store'), [
                'email' => 'blocked@test.local',
                'role' => UserRole::Agent->value,
            ])
            ->assertForbidden();
    }
}
