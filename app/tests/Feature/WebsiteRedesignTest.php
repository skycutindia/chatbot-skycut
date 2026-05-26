<?php

namespace Tests\Feature;

use App\Models\ChatbotConfiguration;
use App\Models\Organization;
use App\Models\QaPair;
use App\Models\QuickAction;
use App\Models\User;
use App\Models\Website;
use App\Services\WidgetPluginExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): array
    {
        $org = Organization::create([
            'name' => 'Redesign Org',
            'slug' => 'redesign-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'redesign-owner@test.local',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return [$user, $org];
    }

    public function test_wizard_step1_and_step2_create_website_with_plugin_files(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->post(route('websites.store.step1'), [
                'name' => 'Acme Store',
                'url' => 'https://acme.test',
                'category' => 'E-commerce',
                'language' => 'en',
                'contact_email' => 'hi@acme.test',
                'is_active' => '1',
            ])
            ->assertRedirect(route('websites.create.bot'));

        $this->actingAs($user)
            ->post(route('websites.store'), [
                'bot_name' => 'Acme Bot',
                'welcome_message' => 'Hello!',
                'primary_color' => '#0d9488',
                'secondary_color' => '#14b8a6',
                'locale' => 'en',
                'typing_animation' => '1',
                'bot_online' => '1',
            ])
            ->assertRedirect();

        $website = Website::where('name', 'Acme Store')->first();
        $this->assertNotNull($website);
        $this->assertSame('Acme Bot', $website->configuration->bot_name);

        $dir = app(WidgetPluginExportService::class)->packageDirectory($website);
        $this->assertFileExists($dir.'/widget.js');
        $this->assertFileExists($dir.'/widget.css');
        $this->assertFileExists($dir.'/config.json');
        $this->assertFileExists($dir.'/install-guide.html');
        $this->assertFileExists($dir.'/README.txt');
    }

    public function test_websites_index_shows_required_columns(): void
    {
        [$user, $org] = $this->owner();

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Column Site',
            'demo_slug' => 'column-site',
            'url' => 'https://column.test',
            'is_active' => true,
        ]);

        ChatbotConfiguration::query()->where('website_id', $website->id)->update(['bot_name' => 'Column Bot']);

        $this->actingAs($user)
            ->get(route('websites.index'))
            ->assertOk()
            ->assertSee('Column Site')
            ->assertSee('Column Bot')
            ->assertSee('Total chats')
            ->assertSee('Edit bot')
            ->assertDontSee('Edit website')
            ->assertDontSee('ws-website-search', false);
    }

    public function test_questions_crud_clone_and_bulk_delete(): void
    {
        [$user, $org] = $this->owner();

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'QA Site',
            'demo_slug' => 'qa-site',
            'is_active' => true,
        ]);

        $qa = QaPair::create([
            'website_id' => $website->id,
            'question' => 'Hours?',
            'answer' => '9-5 weekdays',
            'is_active' => true,
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->get(route('websites.questions.index', $website))
            ->assertOk()
            ->assertSee('Hours?');

        $this->actingAs($user)
            ->post(route('websites.questions.clone', [$website, $qa]))
            ->assertRedirect();

        $this->assertSame(2, $website->qaPairs()->count());

        $this->actingAs($user)
            ->post(route('websites.questions.bulk-delete', $website), ['ids' => $website->qaPairs()->pluck('id')->all()])
            ->assertRedirect();

        $this->assertSame(0, $website->qaPairs()->count());
    }

    public function test_quick_actions_full_crud_and_reorder(): void
    {
        [$user, $org] = $this->owner();

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'QA Buttons Site',
            'demo_slug' => 'qa-buttons',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('websites.quick-actions.index', $website))
            ->assertOk()
            ->assertSee('Quick action buttons')
            ->assertSee('No quick actions yet');

        // Create with custom answer
        $this->actingAs($user)
            ->post(route('websites.quick-actions.store', $website), [
                'label' => 'Get a quote',
                'description' => 'Tell us about your project',
                'icon' => '💰',
                'color' => '#2563eb',
                'action_type' => 'answer',
                'custom_answer' => 'Sure! Just share your timeline and budget and we will respond within an hour.',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $action = $website->quickActions()->first();
        $this->assertNotNull($action);
        $this->assertSame('answer', $action->action_type);
        $this->assertSame('💰', $action->icon);
        $this->assertSame('#2563eb', $action->color);
        $this->assertNotEmpty($action->custom_answer);
        $this->assertTrue($action->is_active);

        // Update
        $this->actingAs($user)
            ->put(route('websites.quick-actions.update', [$website, $action]), [
                'label' => 'Get an instant quote',
                'description' => 'Tell us about your project',
                'icon' => '💸',
                'color' => '#059669',
                'action_type' => 'answer',
                'custom_answer' => 'Updated answer text',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('Get an instant quote', $action->fresh()->label);
        $this->assertSame('Updated answer text', $action->fresh()->custom_answer);

        // Validation: custom_answer required for answer type
        $this->actingAs($user)
            ->post(route('websites.quick-actions.store', $website), [
                'label' => 'Empty answer',
                'action_type' => 'answer',
            ])
            ->assertSessionHasErrors('custom_answer');

        // Validation: action_value required for url type
        $this->actingAs($user)
            ->post(route('websites.quick-actions.store', $website), [
                'label' => 'Bad URL action',
                'action_type' => 'url',
            ])
            ->assertSessionHasErrors('action_value');

        // Toggle active
        $response = $this->actingAs($user)
            ->patchJson(route('websites.quick-actions.toggle', [$website, $action]));
        $response->assertOk()->assertJson(['is_active' => false]);
        $this->assertFalse($action->fresh()->is_active);

        // Duplicate
        $this->actingAs($user)
            ->post(route('websites.quick-actions.duplicate', [$website, $action]))
            ->assertRedirect();
        $this->assertSame(2, $website->quickActions()->count());

        // Reorder
        $ids = $website->quickActions()->orderBy('id')->pluck('id')->toArray();
        $reversed = array_reverse($ids);
        $this->actingAs($user)
            ->postJson(route('websites.quick-actions.reorder', $website), ['order' => $reversed])
            ->assertOk();

        $firstAfter = $website->quickActions()->orderBy('sort_order')->first();
        $this->assertSame($reversed[0], $firstAfter->id);

        // Destroy
        $this->actingAs($user)
            ->delete(route('websites.quick-actions.destroy', [$website, $action]))
            ->assertRedirect();
        $this->assertSame(1, $website->quickActions()->count());
    }

    public function test_widget_config_exposes_custom_answer_and_color(): void
    {
        [, $org] = $this->owner();

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Widget Cfg Site',
            'demo_slug' => 'widget-cfg',
            'is_active' => true,
        ]);

        QuickAction::create([
            'website_id' => $website->id,
            'label' => 'WhatsApp us',
            'description' => '24x7 reply',
            'icon' => '📱',
            'color' => '#16a34a',
            'action_type' => 'answer',
            'custom_answer' => 'Our team replies on WhatsApp within minutes.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $payload = app(\App\Services\WidgetConfigService::class)->buildConfig($website);
        $action = $payload['quick_actions']->first();

        $this->assertSame('answer', $action['action_type']);
        $this->assertSame('#16a34a', $action['color']);
        $this->assertSame('24x7 reply', $action['description']);
        $this->assertStringContainsString('WhatsApp', $action['custom_answer']);
    }

    public function test_embed_regenerate_route(): void
    {
        [$user, $org] = $this->owner();

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Embed Site',
            'demo_slug' => 'embed-site',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('websites.embed.regenerate', $website))
            ->assertRedirect();

        $dir = app(WidgetPluginExportService::class)->packageDirectory($website);
        $this->assertFileExists($dir.'/config.json');
    }
}
