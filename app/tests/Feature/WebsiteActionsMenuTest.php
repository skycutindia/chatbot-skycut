<?php

namespace Tests\Feature;

use App\Models\ChatbotConfiguration;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteActionsMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWebsite(): array
    {
        $org = Organization::create([
            'name' => 'Actions Org',
            'slug' => 'actions-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'actions-owner@test.local',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Actions Site',
            'demo_slug' => 'actions-site',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        ChatbotConfiguration::query()->where('website_id', $website->id)->update([
            'bot_name' => 'Test Bot',
        ]);

        return [$user, $website];
    }

    public function test_workspace_nav_on_training_hub(): void
    {
        [$user, $website] = $this->ownerWithWebsite();

        $this->actingAs($user)
            ->get(route('websites.training.index', $website))
            ->assertOk()
            ->assertSee('ws-nav', false)
            ->assertSee('Training', false)
            ->assertSee('Generate with AI', false)
            ->assertDontSee('data-training-tab', false);
    }

    public function test_all_website_action_pages_load_for_owner(): void
    {
        [$user, $website] = $this->ownerWithWebsite();

        $routes = [
            ['websites.edit', [$website]],
            ['websites.edit.bot', [$website]],
            ['websites.questions.index', [$website]],
            ['websites.embed', [$website]],
            ['websites.training.index', [$website]],
            ['websites.knowledge.index', [$website, 'q' => 'test']],
            ['websites.quick-actions.index', [$website]],
            ['websites.webhooks.index', [$website]],
            ['websites.analytics', [$website]],
            ['inbox.index', ['website_id' => $website->id]],
        ];

        foreach ($routes as [$name, $params]) {
            $this->actingAs($user)
                ->get(route($name, $params))
                ->assertOk();
        }
    }

    public function test_toggle_status_and_duplicate_from_actions_menu(): void
    {
        [$user, $website] = $this->ownerWithWebsite();

        $this->actingAs($user)
            ->post(route('websites.toggle-status', $website))
            ->assertRedirect();

        $website->refresh();
        $this->assertFalse($website->is_active);

        $this->actingAs($user)
            ->post(route('websites.duplicate', $website))
            ->assertRedirect();

        $this->assertSame(2, Website::where('organization_id', $website->organization_id)->count());
    }

    public function test_websites_index_shows_edit_bot_without_overflow_menu(): void
    {
        [$user, $website] = $this->ownerWithWebsite();

        $this->actingAs($user)
            ->get(route('websites.index'))
            ->assertOk()
            ->assertDontSee('id="website-actions-'.$website->id.'"', false)
            ->assertSee('ws-site-card')
            ->assertSee('Edit bot')
            ->assertDontSee('ws-website-search', false);
    }

    public function test_workspace_header_includes_actions_menu(): void
    {
        [$user, $website] = $this->ownerWithWebsite();

        $this->actingAs($user)
            ->get(route('websites.edit.bot', $website))
            ->assertOk()
            ->assertSee('website-actions-'.$website->id, false)
            ->assertSee('Training');
    }
}
