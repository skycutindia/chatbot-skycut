<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationNote;
use App\Models\ConversationNoteMention;
use App\Services\ChatMentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentMentionController extends Controller
{
    public function search(Request $request, ChatMentionService $mentions): JsonResponse
    {
        abort_unless($request->user()->roleEnum()->canHandleLiveChat(), 403);

        $query = trim((string) $request->query('q', ''));

        $agents = $mentions->mentionableAgents($request->user()->organization_id, $query !== '' ? $query : null)
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'label' => $user->name,
            ]);

        return response()->json(['agents' => $agents]);
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->roleEnum()->canHandleLiveChat(), 403);

        $mentions = ConversationNoteMention::query()
            ->with(['note.user', 'conversation.website', 'mentionedBy'])
            ->where('mentioned_user_id', $request->user()->id)
            ->whereNull('read_at')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (ConversationNoteMention $mention) => [
                'id' => $mention->id,
                'conversation_id' => $mention->conversation_id,
                'author' => $mention->mentionedBy?->name,
                'visitor' => $mention->conversation?->visitor_name ?: 'Visitor',
                'website' => $mention->conversation?->website?->name,
                'excerpt' => Str::limit($mention->note?->body ?? '', 120),
                'url' => route('inbox.index', ['conversation' => $mention->conversation_id]),
                'created_at' => $mention->created_at?->diffForHumans(),
            ]);

        return response()->json([
            'count' => $mentions->count(),
            'mentions' => $mentions,
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        abort_unless($request->user()->roleEnum()->canHandleLiveChat(), 403);

        ConversationNoteMention::query()
            ->where('mentioned_user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
