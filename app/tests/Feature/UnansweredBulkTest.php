<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\UnansweredQuestion;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnansweredBulkTest extends TestCase
{
    use RefreshDatabase;

    protected function fixtures(): array
    {
        $org = Organization::create([
            'name' => 'Bulk Org',
            'slug' => 'bulk-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Manager',
            'email' => 'bulk@test.local',
            'password' => bcrypt('password'),
            'role' => UserRole::Manager->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Bulk Site',
            'demo_slug' => 'bulk-site',
            'is_active' => true,
        ]);

        $q1 = UnansweredQuestion::create([
            'website_id' => $website->id,
            'visitor_message' => 'What are your hours?',
            'source' => 'low_confidence',
            'status' => 'open',
        ]);

        $q2 = UnansweredQuestion::create([
            'website_id' => $website->id,
            'visitor_message' => 'Do you ship internationally?',
            'source' => 'fallback',
            'status' => 'open',
        ]);

        return [$user, $website, $q1, $q2];
    }

    public function test_bulk_dismiss_open_questions(): void
    {
        [$user, $website, $q1, $q2] = $this->fixtures();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user)
            ->post(route('websites.unanswered.bulk', $website), [
                'ids' => [$q1->id, $q2->id],
                'action' => 'dismiss',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals('dismissed', $q1->fresh()->status);
        $this->assertEquals('dismissed', $q2->fresh()->status);
    }

    public function test_bulk_promote_creates_qa_pairs(): void
    {
        [$user, $website, $q1, $q2] = $this->fixtures();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user)
            ->post(route('websites.unanswered.bulk', $website), [
                'ids' => [$q1->id, $q2->id],
                'action' => 'promote',
                'answer' => 'Contact support@example.test for details.',
                'category' => 'General',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals('resolved', $q1->fresh()->status);
        $this->assertEquals('resolved', $q2->fresh()->status);
        $this->assertDatabaseCount('qa_pairs', 2);
        $this->assertDatabaseHas('qa_pairs', [
            'website_id' => $website->id,
            'question' => 'What are your hours?',
            'answer' => 'Contact support@example.test for details.',
        ]);
    }

    public function test_bulk_promote_accepts_per_question_answers(): void
    {
        [$user, $website, $q1, $q2] = $this->fixtures();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user)
            ->post(route('websites.unanswered.bulk', $website), [
                'ids' => [$q1->id, $q2->id],
                'action' => 'promote',
                'answers' => [
                    $q1->id => 'We are open Mon–Fri 9–5.',
                    $q2->id => 'Yes, we ship worldwide.',
                ],
                'category' => 'FAQ',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('qa_pairs', [
            'website_id' => $website->id,
            'question' => 'What are your hours?',
            'answer' => 'We are open Mon–Fri 9–5.',
        ]);
        $this->assertDatabaseHas('qa_pairs', [
            'website_id' => $website->id,
            'question' => 'Do you ship internationally?',
            'answer' => 'Yes, we ship worldwide.',
        ]);
    }
}
