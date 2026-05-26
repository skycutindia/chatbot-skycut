<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\QaPair;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class QaPairImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_qa_csv_import_creates_pairs_with_keywords_and_category(): void
    {
        $org = Organization::create([
            'name' => 'QA Import Org',
            'slug' => 'qa-import-org',
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'Admin',
            'email' => 'admin@qa-import.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Owner->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'QA Site',
            'demo_slug' => 'qa-site',
            'url' => 'https://qa.test',
            'language' => 'en',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        $csv = "question,answer,keywords,category\n";
        $csv .= "What are your hours?,9-5 weekdays,hours;schedule,General\n";
        $csv .= "Pricing?,See our plans page,pricing cost,Sales\n";

        $file = UploadedFile::fake()->createWithContent('qa.csv', $csv);

        $this->actingAs($user)
            ->post(route('websites.qa.import', $website), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(2, QaPair::where('website_id', $website->id)->count());

        $hours = QaPair::where('website_id', $website->id)->where('question', 'like', '%hours%')->first();
        $this->assertNotNull($hours);
        $this->assertSame(['hours', 'schedule'], $hours->trigger_keywords);
        $this->assertSame('General', $hours->category);
    }
}
