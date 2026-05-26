<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Website;
use App\Models\WhatsappChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WhatsAppService
{
    public function graphUrl(string $path): string
    {
        $version = config('chatbot.whatsapp.graph_version', 'v21.0');

        return "https://graph.facebook.com/{$version}/{$path}";
    }

    public function sendText(WhatsappChannel $channel, string $to, string $body): bool
    {
        if (! $channel->is_active || trim($body) === '') {
            return false;
        }

        $response = Http::withToken($channel->access_token)
            ->post($this->graphUrl("{$channel->phone_number_id}/messages"), [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => Str::limit($body, 4096, '')],
            ]);

        return $response->successful();
    }

    public function verifyChallenge(?string $mode, ?string $token, ?string $challenge, WhatsappChannel $channel): ?string
    {
        if ($mode === 'subscribe' && $token === $channel->verify_token) {
            return $challenge;
        }

        return null;
    }

    public function findChannelByPhoneNumberId(string $phoneNumberId): ?WhatsappChannel
    {
        return WhatsappChannel::query()
            ->where('phone_number_id', $phoneNumberId)
            ->where('is_active', true)
            ->first();
    }

    public function handleInboundPayload(array $payload, ChatResponseService $chatService): void
    {
        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            return;
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

                if (! $phoneNumberId) {
                    continue;
                }

                $channel = $this->findChannelByPhoneNumberId($phoneNumberId);
                if (! $channel) {
                    continue;
                }

                $website = $this->resolveWebsite($channel);
                if (! $website) {
                    continue;
                }

                $contactName = $value['contacts'][0]['profile']['name'] ?? null;

                foreach ($value['messages'] ?? [] as $incoming) {
                    if (($incoming['type'] ?? '') !== 'text') {
                        continue;
                    }

                    $text = $incoming['text']['body'] ?? '';
                    $from = (string) ($incoming['from'] ?? '');

                    if ($from === '' || trim($text) === '') {
                        continue;
                    }

                    $this->processInboundText($channel, $website, $from, $text, $contactName, $chatService);
                }
            }
        }
    }

    protected function resolveWebsite(WhatsappChannel $channel): ?Website
    {
        if ($channel->website_id) {
            return $channel->website()->where('is_active', true)->first();
        }

        return Website::query()
            ->where('organization_id', $channel->organization_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    protected function processInboundText(
        WhatsappChannel $channel,
        Website $website,
        string $from,
        string $text,
        ?string $contactName,
        ChatResponseService $chatService,
    ): void {
        $conversation = Conversation::query()
            ->where('website_id', $website->id)
            ->where('channel', 'whatsapp')
            ->where('channel_contact_id', $from)
            ->whereIn('status', ['open', 'awaiting_agent'])
            ->latest('last_message_at')
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'website_id' => $website->id,
                'visitor_id' => 'wa:'.$from,
                'visitor_name' => $contactName,
                'visitor_phone' => '+'.$from,
                'channel' => 'whatsapp',
                'channel_contact_id' => $from,
                'status' => 'open',
                'mode' => 'ai',
                'last_message_at' => now(),
            ]);

            app(ChatAutomationService::class)->applyForNewConversation($conversation);
        }

        $conversation->messages()->create([
            'sender_type' => 'visitor',
            'content' => $text,
            'source' => 'whatsapp',
        ]);

        $conversation->update(['last_message_at' => now()]);

        if ($conversation->fresh()->isAwaitingAgent()) {
            return;
        }

        $result = $chatService->respond($website, $conversation, $text);
        $reply = $result['message']['content'] ?? $result['content'] ?? null;

        if ($reply) {
            $this->sendText($channel, $from, $reply);
        }
    }

    public function sendAgentReply(Conversation $conversation, Message $message): bool
    {
        if ($conversation->channel !== 'whatsapp' || ! $conversation->channel_contact_id) {
            return false;
        }

        $channel = WhatsappChannel::query()
            ->where('organization_id', $conversation->website->organization_id)
            ->where('is_active', true)
            ->first();

        if (! $channel) {
            return false;
        }

        return $this->sendText($channel, $conversation->channel_contact_id, (string) $message->content);
    }
}
