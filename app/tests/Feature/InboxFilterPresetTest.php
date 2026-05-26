<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\InboxFilterPreset;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxFilterPresetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_agent_can_save_and_list_inbox_filter_preset(): void
    {
        $org = Organization::create([
            'name' => 'Preset Org',
            'slug' => 'preset-org',
            'is_active' => true,
        ]);

        $agent = User::create([
            'organization_id' => $org->id,
            'name' => 'Agent',
            'email' => 'preset-agent@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($agent)
            ->postJson(route('inbox.filter-presets.store'), [
                'name' => 'Awaiting only',
                'filters' => [
                    'view' => 'awaiting',
                    'sort' => 'newest',
                    'q' => '',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('preset.name', 'Awaiting only');

        $preset = InboxFilterPreset::query()->where('user_id', $agent->id)->first();
        $this->assertNotNull($preset);
        $this->assertSame('awaiting', $preset->filters['view']);

        $this->actingAs($agent)
            ->getJson(route('inbox.filter-presets.index'))
            ->assertOk()
            ->assertJsonCount(1, 'presets')
            ->assertJsonPath('presets.0.name', 'Awaiting only');

        $url = $preset->toQueryParams();
        $this->assertArrayHasKey('awaiting', $url);
        $this->assertSame('1', $url['awaiting']);
    }

    public function test_agent_cannot_delete_another_users_preset(): void
    {
        $org = Organization::create([
            'name' => 'Preset Org 2',
            'slug' => 'preset-org-2',
            'is_active' => true,
        ]);

        $owner = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'preset-owner@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $other = User::create([
            'organization_id' => $org->id,
            'name' => 'Other',
            'email' => 'preset-other@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $preset = InboxFilterPreset::create([
            'user_id' => $owner->id,
            'name' => 'Mine',
            'filters' => ['view' => 'all'],
        ]);

        $this->actingAs($other)
            ->deleteJson(route('inbox.filter-presets.destroy', $preset))
            ->assertNotFound();
    }
}
