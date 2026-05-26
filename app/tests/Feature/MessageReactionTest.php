<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Organization;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageReactionTest extends TestCase
{
    use RefreshDatabase;

    protected function fixtures(): array
    {
        $org = Organization::create([
            'name' => 'React Org',
            'slug' => 'react-org',
            'is_active' => true,
        ]);

        $website = Website::create([
            'organization_id' => $org->id,
            'name' => 'React Site',
            'demo_slug' => 'react-site',
            'url' => 'https://react.test',
            'domain' => 'react.test',
            'language' => 'en',
            'is_active' => true,
            'widget_enabled' => true,
        ]);

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => 'visitor-react-1',
            'status' => 'open',
            'mode' => 'ai',
            'last_message_at' => now(),
        ]);

        $agentMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'content' => 'Happy to help!',
            'source' => 'live_agent',
        ]);

        return [$website, $conversation, $agentMessage];
    }

    public function test_visitor_can_react_to_agent_message(): void
    {
        [$website, $conversation, $message] = $this->fixtures();

        $response = $this->postJson('/api/widget/'.$website->bot_token.'/reactions', [
            'visitor_id' => 'visitor-react-1',
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'emoji' => '👍',
        ]);

        $response->assertOk()
            ->assertJsonPath('message_id', $message->id)
            ->assertJsonPath('removed', false)
            ->assertJsonPath('reactions.0.emoji', '👍')
            ->assertJsonPath('reactions.0.count', 1)
            ->assertJsonPath('reactions.0.mine', true);

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'reactor_type' => 'visitor',
            'reactor_key' => 'visitor-react-1',
            'emoji' => '👍',
        ]);
    }

    public function test_tapping_same_reaction_removes_it(): void
    {
        [$website, $conversation, $message] = $this->fixtures();

        MessageReaction::create([
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'reactor_type' => 'visitor',
            'reactor_key' => 'visitor-react-1',
            'emoji' => '👍',
        ]);

        $this->postJson('/api/widget/'.$website->bot_token.'/reactions', [
            'visitor_id' => 'visitor-react-1',
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'emoji' => '👍',
        ])
            ->assertOk()
            ->assertJsonPath('removed', true)
            ->assertJsonPath('reactions', []);

        $this->assertDatabaseMissing('message_reactions', [
            'message_id' => $message->id,
            'reactor_key' => 'visitor-react-1',
        ]);
    }

    public function test_invalid_emoji_is_rejected(): void
    {
        [$website, $conversation, $message] = $this->fixtures();

        $this->postJson('/api/widget/'.$website->bot_token.'/reactions', [
            'visitor_id' => 'visitor-react-1',
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'emoji' => '🦄',
        ])->assertStatus(422);
    }

    public function test_poll_includes_reaction_summaries(): void
    {
        [$website, $conversation, $message] = $this->fixtures();

        MessageReaction::create([
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'reactor_type' => 'visitor',
            'reactor_key' => 'visitor-react-1',
            'emoji' => '❤️',
        ]);

        $this->getJson('/api/widget/'.$website->bot_token.'/poll?'.http_build_query([
            'visitor_id' => 'visitor-react-1',
            'conversation_id' => $conversation->id,
            'after_id' => 0,
        ]))
            ->assertOk()
            ->assertJsonPath('reactions.'.$message->id.'.0.emoji', '❤️')
            ->assertJsonPath('reactions.'.$message->id.'.0.count', 1);
    }
}
