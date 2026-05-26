<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AgentQuickReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class AgentQuickReplyController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()->isLiveChatAgent(), 403);
        $user = $request->user();
        $validated = $request->validate([
            'title' => 'nullable|string|max:120',
            'body' => 'required|string|max:4000',
        ]);

        $maxOrder = (int) AgentQuickReply::query()
            ->where('user_id', $user->id)
            ->max('sort_order');

        $reply = AgentQuickReply::create([
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
            'title' => $validated['title'] ?: null,
            'body' => $validated['body'],
            'sort_order' => $maxOrder + 1,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'reply' => $this->replyPayload($reply),
            ]);
        }

        return back()->with('success', 'Quick reply saved.');
    }

    public function update(Request $request, AgentQuickReply $agentQuickReply): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()->isLiveChatAgent(), 403);
        $this->authorizeReply($request, $agentQuickReply);

        $validated = $request->validate([
            'title' => 'nullable|string|max:120',
            'body' => 'required|string|max:4000',
        ]);

        $agentQuickReply->update([
            'title' => $validated['title'] ?: null,
            'body' => $validated['body'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'reply' => $this->replyPayload($agentQuickReply),
            ]);
        }

        return back()->with('success', 'Quick reply updated.');
    }

    public function destroy(Request $request, AgentQuickReply $agentQuickReply): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()->isLiveChatAgent(), 403);
        $this->authorizeReply($request, $agentQuickReply);
        $agentQuickReply->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Quick reply removed.');
    }

    private function authorizeReply(Request $request, AgentQuickReply $agentQuickReply): void
    {
        $user = $request->user();
        abort_unless(
            $agentQuickReply->user_id === $user->id
            && $agentQuickReply->organization_id === $user->organization_id,
            403
        );
    }

    /** @return array<string, mixed> */
    private function replyPayload(AgentQuickReply $reply): array
    {
        return [
            'id' => $reply->id,
            'title' => $reply->title,
            'body' => $reply->body,
            'label' => $reply->displayLabel(),
            'delete_url' => route('inbox.quick-replies.destroy', $reply),
            'update_url' => route('inbox.quick-replies.update', $reply),
            'preview' => \Illuminate\Support\Str::limit($reply->body, 72),
        ];
    }
}
