<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\PlatformSetting;
use App\Services\AiConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AiConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_syncs_env_key_to_platform_when_platform_empty(): void
    {
        Config::set('chatbot.openai.api_key', 'sk-from-env-test');

        app(AiConfigService::class)->ensurePlatformKeyFromEnvironment();

        $this->assertSame('sk-from-env-test', PlatformSetting::get('openai_api_key'));
    }

    public function test_prefers_platform_over_stored_org_key_when_org_flag_off(): void
    {
        PlatformSetting::set('openai_api_key', 'sk-platform');
        $org = Organization::create([
            'name' => 'O',
            'slug' => 'o',
            'is_active' => true,
            'settings' => [
                'openai_api_key' => 'sk-org-unused',
                'use_org_openai_key' => false,
            ],
        ]);

        $this->assertSame('sk-platform', app(AiConfigService::class)->resolveApiKey($org));
    }
}
