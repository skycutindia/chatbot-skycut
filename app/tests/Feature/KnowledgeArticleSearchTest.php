<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use App\Services\KnowledgeIndexerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeArticleSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_knowledge_index_filters_articles_by_search_query(): void
    {
        $org = Organization::create([
            'name' => 'KB Org',
            'slug' => 'kb-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'kb-owner@test.local',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'KB Site',
            'demo_slug' => 'kb-site',
            'is_active' => true,
        ]);

        $indexer = app(KnowledgeIndexerService::class);
        $indexer->indexArticle($website, 'Shipping policy', 'We ship within 3 business days.');
        $indexer->indexArticle($website, 'Refund policy', 'Returns accepted within 30 days.');

        $this->actingAs($user)
            ->get(route('websites.knowledge.index', ['website' => $website, 'q' => 'refund']))
            ->assertOk()
            ->assertSee('Refund policy')
            ->assertDontSee('Shipping policy');
    }

    public function test_knowledge_article_can_be_updated(): void
    {
        $org = Organization::create([
            'name' => 'KB Org 2',
            'slug' => 'kb-org-2',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Owner',
            'email' => 'kb-owner2@test.local',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'KB Site 2',
            'demo_slug' => 'kb-site-2',
            'is_active' => true,
        ]);

        $article = app(KnowledgeIndexerService::class)->indexArticle($website, 'Old title', 'Old body text.');

        $this->actingAs($user)
            ->put(route('websites.knowledge.articles.update', [$website, $article]), [
                'title' => 'Updated title',
                'content' => 'Updated body content.',
                'is_published' => '1',
            ])
            ->assertRedirect();

        $article->refresh();
        $this->assertSame('Updated title', $article->title);
        $this->assertSame('Updated body content.', $article->content);
    }
}
