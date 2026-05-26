<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Events\AgentMentionedInNote;
use App\Models\Conversation;
use App\Models\ConversationNoteMention;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use App\Services\ChatMentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ChatMentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function fixtures(): array
    {
        $org = Organization::create([
            'name' => 'Mention Org',
            'slug' => 'mention-org',
            'is_active' => true,
        ]);

        $author = User::create([
            'organization_id' => $org->id,
            'name' => 'Note Author',
            'email' => 'author@mention.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $mentioned = User::create([
            'organization_id' => $org->id,
            'name' => 'Support Agent',
            'email' => 'support@mention.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Agent->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'Mention Site',
            'demo_slug' => 'mention-site',
            'url' => 'https://mention.test',
            'domain' => 'mention.test',
            'is_active' => true,
        ]);

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'v_mention',
            'visitor_name' => 'Pat Visitor',
            'status' => 'open',
            'mode' => 'human',
            'last_message_at' => now(),
        ]);

        return [$author, $mentioned, $conversation];
    }

    public function test_mention_search_returns_org_agents(): void
    {
        [$author, $mentioned] = $this->fixtures();

        $response = $this->actingAs($author)
            ->getJson(route('inbox.mentions.search', ['q' => 'Support']));

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Support Agent']);
    }

    public function test_internal_note_creates_mention_and_event(): void
    {
        Event::fake([AgentMentionedInNote::class]);
        [$author, $mentioned, $conversation] = $this->fixtures();

        $this->actingAs($author)
            ->post(route('inbox.notes.store', $conversation), [
                'body' => 'Please review this chat @Support Agent',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('conversation_note_mentions', [
            'conversation_id' => $conversation->id,
            'mentioned_user_id' => $mentioned->id,
            'mentioned_by_user_id' => $author->id,
        ]);

        Event::assertDispatched(AgentMentionedInNote::class);
    }

    public function test_opening_conversation_marks_mentions_read(): void
    {
        [$author, $mentioned, $conversation] = $this->fixtures();

        $this->actingAs($author)
            ->post(route('inbox.notes.store', $conversation), [
                'body' => 'FYI @Support Agent',
            ]);

        $mention = ConversationNoteMention::query()
            ->where('mentioned_user_id', $mentioned->id)
            ->first();

        $this->assertNotNull($mention);
        $this->assertNull($mention->read_at);

        $this->actingAs($mentioned)
            ->get(route('inbox.index', ['conversation' => $conversation->id]))
            ->assertOk();

        $this->assertNotNull($mention->fresh()->read_at);
    }

    public function test_resolve_mentioned_users_from_body(): void
    {
        [, $mentioned] = $this->fixtures();
        $service = app(ChatMentionService::class);

        $users = $service->resolveMentionedUsers(
            'Looping in @Support Agent for billing',
            $mentioned->organization_id
        );

        $this->assertTrue($users->contains('id', $mentioned->id));
    }
}
