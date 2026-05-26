<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\AiConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        config()->set('chatbot.openai.api_key', null);
    }

    private function owner(): array
    {
        $org = Organization::create([
            'name' => 'Settings Org',
            'slug' => 'settings-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'owner-settings@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return [$user, $org];
    }

    public function test_settings_hub_loads_for_owner(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->get(route('settings.index', ['tab' => 'ai']))
            ->assertOk()
            ->assertSee('Workspace OpenAI API key', false);

        $this->actingAs($user)
            ->get(route('settings.index', ['tab' => 'dashboard']))
            ->assertOk()
            ->assertSee('Dashboard modules', false);
    }

    public function test_owner_can_save_org_openai_key(): void
    {
        [$user, $org] = $this->owner();

        $this->actingAs($user)
            ->put(route('settings.ai.update'), [
                'openai_api_key' => 'sk-test-org-key-1234567890',
                'openai_default_model' => 'gpt-4o-mini',
            ])
            ->assertRedirect(route('settings.index', ['tab' => 'ai']));

        $org->refresh();
        $this->assertTrue($org->settings['use_org_openai_key']);
        $this->assertSame('sk-test-org-key-1234567890', $org->settings['openai_api_key']);

        $this->assertSame(
            'sk-test-org-key-1234567890',
            app(AiConfigService::class)->resolveApiKey($org)
        );
    }

    public function test_org_key_used_when_saved_even_if_use_org_flag_was_off(): void
    {
        [$user, $org] = $this->owner();
        $org->update([
            'settings' => [
                'openai_api_key' => 'sk-stored-org-key-only',
                'use_org_openai_key' => false,
            ],
        ]);

        $this->assertSame(
            'sk-stored-org-key-only',
            app(AiConfigService::class)->resolveApiKey($org->fresh())
        );
    }

    public function test_platform_key_used_when_org_key_disabled(): void
    {
        [$user, $org] = $this->owner();
        PlatformSetting::set('openai_api_key', 'sk-platform-key-abcdefghij');

        $this->actingAs($user)
            ->put(route('settings.ai.update'), [
                'use_org_openai_key' => '0',
            ]);

        $this->assertSame(
            'sk-platform-key-abcdefghij',
            app(AiConfigService::class)->resolveApiKey($org)
        );
    }
}
