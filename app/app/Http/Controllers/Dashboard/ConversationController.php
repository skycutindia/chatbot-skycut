<?php

namespace App\Http\Controllers\Dashboard;

use App\Events\ConversationUpdated;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationNote;
use App\Models\Message;
use App\Models\Website;
use App\Services\ChatAttachmentService;
use App\Services\ChatMentionService;
use App\Services\ChatReadReceiptService;
use App\Services\LiveHandoffService;
use App\Services\MessageReactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request, Website $website): View
    {
        $conversations = $website->conversations()
            ->with('assignedUser')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('last_message_at')
            ->paginate(25);

        return view('dashboard.conversations.index', compact('website', 'conversations'));
    }

    public function replyFromInbox(Request $request, Conversation $conversation, ChatAttachmentService $attachments): JsonResponse
    {
        abort_unless($conversation->website->organization_id === $request->user()->organization_id, 403);
        abort_unless($request->user()->roleEnum()->canHandleLiveChat(), 403);

        return $this->reply($request, $conversation->website, $conversation, $attachments);
    }

    public function show(Website $website, Conversation $conversation): View
    {
        abort_unless($conversation->website_id === $website->id, 404);
        $conversation->load(['messages', 'internalNotes.user', 'lead', 'rating', 'events.user']);

        $canned = \App\Models\CannedResponse::query()
            ->where('organization_id', auth()->user()->organization_id)
            ->where(function ($q) use ($website) {
                $q->whereNull('website_id')->orWhere('website_id', $website->id);
            })
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        return view('dashboard.conversations.show', compact('website', 'conversation', 'canned'));
    }

    public function messages(Request $request, Website $website, Conversation $conversation, ChatAttachmentService $attachments, ChatReadReceiptService $readReceipts, MessageReactionService $reactions): JsonResponse
    {
        abort_unless($conversation->website_id === $website->id, 404);

        $afterId = (int) $request->query('after_id', 0);

        $messageModels = $conversation->messages()
            ->with('attachments')
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->get();

        $readReceipts->markDeliveredToAgent(
            $conversation,
            $messageModels->where('sender_type', 'visitor')->pluck('id')->all()
        );
        $readReceipts->markReadByAgent($conversation);

        $messages = $conversation->messages()
            ->with('attachments')
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->get()
            ->map(fn (Message $m) => $attachments->formatMessage($m));

        $receiptUpdates = $conversation->messages()
            ->where('sender_type', 'agent')
            ->get(['id', 'delivered_at', 'read_at'])
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'receipt_status' => $readReceipts->receiptStatus($m),
            ])
            ->values();

        return response()->json([
            'messages' => $messages,
            'receipt_updates' => $receiptUpdates,
            'reactions' => $reactions->summarizeForConversation($conversation),
            'status' => $conversation->fresh()->status,
            'mode' => $conversation->fresh()->mode,
        ]);
    }

    public function reply(Request $request, Website $website, Conversation $conversation, ChatAttachmentService $attachments): RedirectResponse|JsonResponse
    {
        abort_unless($conversation->website_id === $website->id, 404);

        $validated = $request->validate(['content' => 'required|string|max:4000']);

        $message = $conversation->messages()->create([
            'sender_type' => 'agent',
            'sender_id' => $request->user()->id,
            'content' => $validated['content'],
            'source' => 'live_agent',
        ]);

        $conversation->update([
            'assigned_user_id' => $request->user()->id,
            'last_message_at' => now(),
            'status' => 'open',
            'mode' => 'human',
            'agent_unread_count' => 0,
        ]);

        if (! $conversation->first_response_at) {
            $conversation->update(['first_response_at' => now()]);
        }

        broadcast(new ConversationUpdated($conversation->fresh(), 'agent_reply'));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => $attachments->formatMessage($message),
            ]);
        }

        return back()->with('success', 'Reply sent.');
    }

    public function close(Website $website, Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->website_id === $website->id, 404);
        $conversation->update(['status' => 'closed', 'closed_at' => now()]);
        broadcast(new ConversationUpdated($conversation->fresh(), 'closed'));

        return redirect()->route('websites.conversations.index', $website)->with('success', 'Conversation closed.');
    }

    public function reopen(Website $website, Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->website_id === $website->id, 404);
        $conversation->update(['status' => 'open', 'closed_at' => null]);
        broadcast(new ConversationUpdated($conversation->fresh(), 'reopened'));

        return back()->with('success', 'Conversation reopened.');
    }

    public function storeNote(Request $request, Website $website, Conversation $conversation, ChatMentionService $mentions): RedirectResponse
    {
        abort_unless($conversation->website_id === $website->id, 404);
        $validated = $request->validate(['body' => 'required|string|max:5000']);

        $note = ConversationNote::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        $mentions->syncNoteMentions($note, $conversation, $request->user());

        return back()->with('success', 'Internal note saved.');
    }

    public function returnToAi(Website $website, Conversation $conversation, LiveHandoffService $handoff): RedirectResponse
    {
        abort_unless($conversation->website_id === $website->id, 404);
        $handoff->returnToAi($conversation);

        return back()->with('success', 'Control returned to AI.');
    }
}
