<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\OperatingHour;
use App\Models\Website;
use App\Events\ConversationUpdated;
use App\Models\ChatEvent;
use App\Services\AnalyticsService;
use App\Services\ChatAttachmentService;
use App\Services\ChatAutomationService;
use App\Services\ChatReadReceiptService;
use App\Services\ChatResponseService;
use App\Services\LeadCaptureService;
use App\Services\LiveHandoffService;
use App\Services\MessageReactionService;
use App\Services\WebhookDispatchService;
use App\Services\WidgetRateLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetChatController extends Controller
{
    public function __construct(
        protected ChatResponseService $chatService,
        protected AnalyticsService $analytics,
        protected WidgetRateLimitService $rateLimiter,
        protected LeadCaptureService $leadCapture,
        protected LiveHandoffService $handoffService,
        protected ChatAttachmentService $attachments,
        protected ChatAutomationService $automation,
        protected ChatReadReceiptService $readReceipts,
        protected MessageReactionService $reactions,
    ) {}

    public function store(Request $request): JsonResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');

        if ($this->rateLimiter->tooManyAttempts($website, $request)) {
            return $this->rateLimiter->rateLimitResponse();
        }

        $validated = $request->validate([
            'message' => 'required|string|max:4000',
            'visitor_id' => 'required|string|max:64',
            'conversation_id' => 'nullable|integer',
            'visitor_name' => 'nullable|string|max:120',
            'visitor_email' => 'nullable|email|max:255',
            'visitor_phone' => 'nullable|string|max:40',
            'visitor_company' => 'nullable|string|max:255',
            'page_url' => 'nullable|url|max:2048',
            'source_url' => 'nullable|url|max:2048',
            'utm_params' => 'nullable|array',
        ]);

        if (! OperatingHour::isWithinHours($website) || ! $website->is_active) {
            $msg = $website->configuration->outside_hours_message
                ?? $website->configuration->offline_message
                ?? 'We are currently offline. Please leave a message.';

            return response()->json([
                'offline' => true,
                'message' => [
                    'content' => $msg,
                    'sender_type' => 'bot',
                    'source' => 'offline',
                ],
            ]);
        }

        $conversation = $this->resolveConversation($website, $validated, $request);

        $conversation->messages()->create([
            'sender_type' => 'visitor',
            'content' => $validated['message'],
            'source' => 'user',
        ]);

        $this->analytics->track($website, 'message_sent', $request, [
            'visitor_id' => $validated['visitor_id'],
            'conversation_id' => $conversation->id,
        ]);

        if ($conversation->isAwaitingAgent()) {
            $conversation->update(['last_message_at' => now()]);

            $handoffNotice = null;
            $existingNotice = $conversation->messages()
                ->whereIn('source', ['handoff_pending', 'handoff'])
                ->where('sender_type', 'bot')
                ->latest('id')
                ->first();

            if (! $existingNotice) {
                $existingNotice = $conversation->messages()->create([
                    'sender_type' => 'bot',
                    'content' => 'Your message was sent. An agent will respond shortly.',
                    'source' => 'handoff_pending',
                ]);
            }

            $handoffNotice = $this->attachments->formatMessage(
                $existingNotice,
                true,
                $website,
                'visitor',
                $validated['visitor_id']
            );

            return response()->json([
                'conversation_id' => $conversation->id,
                'handoff' => true,
                'status' => 'awaiting_agent',
                'mode' => $conversation->mode,
                'message' => $handoffNotice,
                'handoff_notice_repeat' => false,
            ]);
        }

        $result = $this->chatService->respond($website, $conversation, $validated['message']);

        return response()->json(array_merge($result, [
            'conversation_id' => $conversation->id,
        ]));
    }

    public function start(Request $request): JsonResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');

        if ($this->rateLimiter->tooManyAttempts($website, $request)) {
            return $this->rateLimiter->rateLimitResponse();
        }

        $validated = $request->validate([
            'visitor_id' => 'required|string|max:64',
            'visitor_name' => 'required|string|max:120',
            'visitor_phone' => 'required|string|max:40',
            'visitor_email' => 'nullable|email|max:255',
            'visitor_company' => 'nullable|string|max:255',
            'page_url' => 'nullable|url|max:2048',
            'source_url' => 'nullable|url|max:2048',
            'utm_params' => 'nullable|array',
        ]);

        $conversation = $this->resolveConversation($website, $validated, $request);

        $welcome = trim((string) $website->configuration->welcome_message);
        $greetingMessage = null;

        if ($welcome !== '' && ! $conversation->messages()->where('source', 'greeting')->exists()) {
            $greeting = $conversation->messages()->create([
                'sender_type' => 'bot',
                'content' => $welcome,
                'source' => 'greeting',
            ]);
            $greetingMessage = $this->attachments->formatMessage(
                $greeting,
                true,
                $website,
                'visitor',
                $validated['visitor_id']
            );
        }

        $this->analytics->track($website, 'pre_chat_completed', $request, [
            'visitor_id' => $validated['visitor_id'],
            'conversation_id' => $conversation->id,
        ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'greeting' => $greetingMessage,
            'visitor' => [
                'name' => $conversation->visitor_name,
                'email' => $conversation->visitor_email,
                'phone' => $conversation->visitor_phone,
                'company' => $conversation->visitor_company,
            ],
        ]);
    }

    public function close(Request $request): JsonResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');

        if ($this->rateLimiter->tooManyAttempts($website, $request)) {
            return $this->rateLimiter->rateLimitResponse();
        }

        $validated = $request->validate([
            'visitor_id' => 'required|string|max:64',
            'conversation_id' => 'required|integer',
        ]);

        $conversation = Conversation::query()
            ->where('website_id', $website->id)
            ->where('visitor_id', $validated['visitor_id'])
            ->where('id', $validated['conversation_id'])
            ->first();

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        if (! in_array($conversation->status, ['closed', 'resolved'], true)) {
            $conversation->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
            ChatEvent::log($conversation, 'closed', null, ['source' => 'widget']);
            broadcast(new ConversationUpdated($conversation->fresh(), 'closed'));
        }

        $this->analytics->track($website, 'widget_chat_closed', $request, [
            'visitor_id' => $validated['visitor_id'],
            'conversation_id' => $conversation->id,
        ]);

        app(WebhookDispatchService::class)->dispatch($website, 'chat.closed', [
            'conversation_id' => $conversation->id,
            'visitor_id' => $validated['visitor_id'],
            'status' => $conversation->fresh()->status,
        ]);

        return response()->json([
            'ok' => true,
            'conversation_id' => $conversation->id,
            'status' => $conversation->fresh()->status,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');

        $validated = $request->validate([
            'visitor_id' => 'required|string|max:64',
            'conversation_id' => 'nullable|integer',
        ]);

        $conversation = Conversation::query()
            ->where('website_id', $website->id)
            ->where('visitor_id', $validated['visitor_id'])
            ->whereIn('status', ['open', 'awaiting_agent'])
            ->when($validated['conversation_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->latest('last_message_at')
            ->first();

        if (! $conversation) {
            return response()->json(['messages' => []]);
        }

        $messages = $conversation->messages()->with('attachments')->get();
        $this->readReceipts->markDeliveredToVisitor(
            $conversation,
            $messages->whereIn('sender_type', ['agent', 'bot'])->pluck('id')->all()
        );

        if ($request->boolean('mark_read')) {
            $this->readReceipts->markReadByVisitor(
                $conversation,
                $messages->whereIn('sender_type', ['agent', 'bot'])->pluck('id')->all()
            );
            $messages = $conversation->messages()->with('attachments')->get();
        }

        return response()->json([
            'conversation_id' => $conversation->id,
            'mode' => $conversation->mode,
            'status' => $conversation->status,
            'visitor' => [
                'name' => $conversation->visitor_name,
                'email' => $conversation->visitor_email,
                'phone' => $conversation->visitor_phone,
                'company' => $conversation->visitor_company,
            ],
            'messages' => $messages
                ->map(fn (Message $m) => $this->attachments->formatMessage(
                    $m,
                    true,
                    $website,
                    'visitor',
                    $validated['visitor_id']
                )),
        ]);
    }

    public function react(Request $request): JsonResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');

        $validated = $request->validate([
            'visitor_id' => 'required|string|max:64',
            'conversation_id' => 'required|integer',
            'message_id' => 'required|integer',
            'emoji' => 'required|string|max:16',
        ]);

        abort_unless($this->reactions->isAllowedEmoji($validated['emoji']), 422, 'Emoji not allowed.');

        $conversation = Conversation::query()
            ->where('website_id', $website->id)
            ->where('visitor_id', $validated['visitor_id'])
            ->where('id', $validated['conversation_id'])
            ->firstOrFail();

        $message = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('id', $validated['message_id'])
            ->firstOrFail();

        $result = $this->reactions->toggleVisitorReaction(
            $conversation,
            $message,
            $validated['visitor_id'],
            $validated['emoji']
        );

        return response()->json([
            'message_id' => $message->id,
            'reactions' => $result['reactions'],
            'removed' => $result['removed'],
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');

        $validated = $request->validate([
            'visitor_id' => 'required|string|max:64',
            'conversation_id' => 'required|integer',
            'message_ids' => 'required|array',
            'message_ids.*' => 'integer',
        ]);

        $conversation = Conversation::query()
            ->where('website_id', $website->id)
            ->where('visitor_id', $validated['visitor_id'])
            ->where('id', $validated['conversation_id'])
            ->firstOrFail();

        $this->readReceipts->markReadByVisitor($conversation, $validated['message_ids']);

        return response()->json(['ok' => true]);
    }

    public function poll(Request $request): JsonResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');

        $validated = $request->validate([
            'visitor_id' => 'required|string|max:64',
            'conversation_id' => 'required|integer',
            'after_id' => 'nullable|integer',
        ]);

        $conversation = Conversation::query()
            ->where('website_id', $website->id)
            ->where('visitor_id', $validated['visitor_id'])
            ->where('id', $validated['conversation_id'])
            ->first();

        if (! $conversation) {
            return response()->json(['messages' => [], 'status' => 'closed']);
        }

        $afterId = (int) ($validated['after_id'] ?? 0);

        $messageModels = $conversation->messages()
            ->with('attachments')
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->whereIn('sender_type', ['bot', 'agent'])
            ->get();

        $this->readReceipts->markDeliveredToVisitor(
            $conversation,
            $messageModels->pluck('id')->all()
        );

        $messages = $messageModels
            ->map(fn (Message $m) => $this->attachments->formatMessage(
                $m->fresh(),
                true,
                $website,
                'visitor',
                $validated['visitor_id']
            ));

        return response()->json([
            'messages' => $messages,
            'reactions' => $this->reactions->summarizeForConversation(
                $conversation,
                'visitor',
                $validated['visitor_id']
            ),
            'status' => $conversation->status,
            'mode' => $conversation->mode,
            'handoff' => $conversation->isAwaitingAgent(),
        ]);
    }

    public function requestHandoff(Request $request): JsonResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');

        $validated = $request->validate([
            'visitor_id' => 'required|string|max:64',
            'conversation_id' => 'required|integer',
            'visitor_name' => 'nullable|string|max:120',
            'visitor_email' => 'nullable|email|max:255',
            'visitor_phone' => 'nullable|string|max:40',
        ]);

        $conversation = Conversation::query()
            ->where('website_id', $website->id)
            ->where('visitor_id', $validated['visitor_id'])
            ->where('id', $validated['conversation_id'])
            ->firstOrFail();

        $this->leadCapture->enrichConversation($conversation, $validated, $request);
        $conversation = $this->handoffService->requestHuman($website, $conversation);

        $handoffMessage = $conversation->messages()
            ->whereIn('source', ['handoff_pending', 'handoff'])
            ->where('sender_type', 'bot')
            ->latest('id')
            ->first();

        if (! $handoffMessage) {
            $handoffMessage = $conversation->messages()->create([
                'sender_type' => 'bot',
                'content' => 'Connecting you with a live agent. Please hold on.',
                'source' => 'handoff',
            ]);
        }

        return response()->json([
            'conversation_id' => $conversation->id,
            'handoff' => true,
            'status' => 'awaiting_agent',
            'mode' => $conversation->mode,
            'message' => $this->attachments->formatMessage(
                $handoffMessage,
                true,
                $website,
                'visitor',
                $validated['visitor_id']
            ),
        ]);
    }

    protected function resolveConversation(Website $website, array $validated, Request $request): Conversation
    {
        if (! empty($validated['conversation_id'])) {
            $existing = Conversation::query()
                ->where('website_id', $website->id)
                ->where('visitor_id', $validated['visitor_id'])
                ->where('id', $validated['conversation_id'])
                ->whereIn('status', ['open', 'awaiting_agent'])
                ->first();

            if ($existing) {
                return $this->leadCapture->enrichConversation($existing, $validated, $request);
            }
        }

        $open = Conversation::query()
            ->where('website_id', $website->id)
            ->where('visitor_id', $validated['visitor_id'])
            ->whereIn('status', ['open', 'awaiting_agent'])
            ->latest('last_message_at')
            ->first();

        if ($open) {
            return $this->leadCapture->enrichConversation($open, $validated, $request);
        }

        $conversation = Conversation::create([
            'website_id' => $website->id,
            'visitor_id' => $validated['visitor_id'],
            'visitor_name' => $validated['visitor_name'] ?? null,
            'visitor_email' => $validated['visitor_email'] ?? null,
            'visitor_phone' => $validated['visitor_phone'] ?? null,
            'visitor_company' => $validated['visitor_company'] ?? null,
            'page_url' => $validated['page_url'] ?? null,
            'source_url' => $validated['source_url'] ?? $validated['page_url'] ?? null,
            'utm_params' => $validated['utm_params'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'open',
            'mode' => 'ai',
            'last_message_at' => now(),
        ]);

        $this->leadCapture->captureFromConversation($website, $conversation, $request);

        $this->automation->applyForNewConversation($conversation);

        app(WebhookDispatchService::class)->dispatch($website, 'chat.started', [
            'conversation_id' => $conversation->id,
            'visitor_id' => $validated['visitor_id'],
            'visitor_name' => $conversation->visitor_name,
        ]);

        return $conversation;
    }
}
