<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\PlatformSetting;
use App\Models\QaPair;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UrlQaGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function fixtures(): array
    {
        $org = Organization::create([
            'name' => 'Train Org',
            'slug' => 'train-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Trainer',
            'email' => 'trainer@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Train Site',
            'demo_slug' => 'train-site',
            'url' => 'https://example.test',
            'domain' => 'example.test',
            'language' => 'en',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        PlatformSetting::set('openai_api_key', 'test-key');

        return [$user, $website];
    }

    public function test_training_hub_page_loads(): void
    {
        [$user, $website] = $this->fixtures();

        $this->actingAs($user)
            ->get(route('websites.training.index', $website))
            ->assertOk()
            ->assertSee('Generate Q&amp;A from any page', false)
            ->assertSee('data-training-hub', false);
    }

    public function test_generate_qa_from_url_stores_draft_in_session(): void
    {
        [$user, $website] = $this->fixtures();

        Http::fake([
            'https://example.test/*' => Http::response(
                '<html><head><title>Example</title></head><body><p>'.str_repeat('We sell printers and cutters. ', 20).'</p></body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'pairs' => [[
                                'question' => 'What do you sell?',
                                'answer' => 'Printers and cutters.',
                                'trigger_keywords' => ['printers', 'products'],
                            ]],
                        ]),
                    ],
                ]],
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('websites.training.generate-qa', $website), [
                'url' => 'https://example.test/about',
                'max_pairs' => 5,
            ])
            ->assertRedirect(route('websites.training.index', $website));

        $this->actingAs($user)
            ->get(route('websites.training.index', $website))
            ->assertSee('What do you sell?', false)
            ->assertSee('printers', false);
    }

    public function test_approve_qa_draft_saves_to_qa_pairs(): void
    {
        [$user, $website] = $this->fixtures();

        $this->actingAs($user)
            ->withSession([
                'training_qa_draft_'.$website->id => [
                    'url' => 'https://example.test',
                    'title' => 'Example',
                    'pairs' => [[
                        'question' => 'Test Q?',
                        'answer' => 'Test A.',
                        'trigger_keywords' => ['test'],
                    ]],
                ],
            ])
            ->post(route('websites.training.approve-qa', $website), [
                'items' => [[
                    'approve' => '1',
                    'question' => 'Test Q?',
                    'answer' => 'Test A.',
                    'trigger_keywords' => 'test, help',
                    'category' => 'From URL',
                ]],
            ])
            ->assertRedirect(route('websites.questions.index', $website));

        $this->assertDatabaseHas('qa_pairs', [
            'website_id' => $website->id,
            'question' => 'Test Q?',
            'answer' => 'Test A.',
        ]);

        $pair = QaPair::query()->where('website_id', $website->id)->first();
        $this->assertContains('test', $pair->trigger_keywords);
    }
}
