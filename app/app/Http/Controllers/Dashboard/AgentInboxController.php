<?php

namespace App\Http\Controllers\Dashboard;

use App\Events\ConversationUpdated;
use App\Http\Controllers\Concerns\AuthorizesTenantRole;
use App\Http\Controllers\Controller;
use App\Models\ChatEvent;
use App\Models\Conversation;
use App\Models\ConversationNote;
use App\Models\Department;
use App\Models\User;
use App\Models\Website;
use App\Services\AgentAssignmentService;
use App\Services\AgentPresenceService;
use App\Services\AgentQuickReplyService;
use App\Services\ChatMentionService;
use App\Services\ChatReadReceiptService;
use App\Services\ConversationExportService;
use App\Services\ConversationQueryService;
use App\Services\LeadCaptureService;
use App\Services\LiveHandoffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentInboxController extends Controller
{
    use AuthorizesTenantRole;

    public function __construct(
        protected ConversationQueryService $queryService,
        protected AgentPresenceService $presence,
        protected AgentAssignmentService $assignment,
        protected ChatReadReceiptService $readReceipts,
        protected ChatMentionService $mentions,
    ) {}

    public function index(Request $request): View
    {
        $this->presence->touch($request->user());

        $conversations = $this->queryService
            ->forOrganization($request->user(), $request)
            ->paginate(40)
            ->withQueryString();

        $websites = Website::query()
            ->when($request->user()->organization_id, fn ($q) => $q->where('organization_id', $request->user()->organization_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $agents = User::query()
            ->when($request->user()->organization_id, fn ($q) => $q->where('organization_id', $request->user()->organization_id))
            ->whereIn('role', ['agent', 'manager', 'admin', 'owner'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $departments = Department::query()
            ->where('organization_id', $request->user()->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $active = null;
        $activeWebsite = null;
        $canned = collect();

        if ($request->filled('conversation')) {
            $active = Conversation::with(['website', 'messages.attachments', 'messages.reactions', 'internalNotes.user', 'lead', 'rating', 'assignedUser', 'department'])
                ->find($request->integer('conversation'));

            if ($active && $this->canAccess($active)) {
                $activeWebsite = $active->website;
                $active->update(['agent_unread_count' => 0]);
                $this->readReceipts->markReadByAgent($active);
                $this->mentions->markReadForConversation($request->user(), $active);
                $active->unsetRelation('messages');
                $active->load(['messages.attachments', 'messages.reactions']);

                $canned = \App\Models\CannedResponse::query()
                    ->where('organization_id', $request->user()->organization_id)
                    ->where(function ($q) use ($activeWebsite) {
                        $q->whereNull('website_id')->orWhere('website_id', $activeWebsite->id);
                    })
                    ->where('is_active', true)
                    ->orderBy('title')
                    ->get();
            } else {
                $active = null;
            }
        }

        $queueStats = $this->inboxQueueStats($request->user());
        $agentQuickReplies = app(AgentQuickReplyService::class)->forInbox($request->user());

        return view('dashboard.inbox.index', compact(
            'conversations', 'websites', 'agents', 'departments', 'active', 'activeWebsite', 'canned', 'queueStats', 'agentQuickReplies'
        ));
    }

    public function export(Request $request, ConversationExportService $exports): StreamedResponse
    {
        $filename = 'inbox-conversations-'.now()->format('Y-m-d-His').'.csv';

        return Response::streamDownload(function () use ($exports, $request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $exports->csvHeaders());

            $exports->exportQuery($request->user(), $request)
                ->orderByDesc('last_message_at')
                ->chunk(200, function ($conversations) use ($exports, $out) {
                    foreach ($conversations as $conversation) {
                        fputcsv($out, $exports->csvRow($conversation));
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function archive(Request $request): View
    {
        $request->merge(['view' => 'archive']);

        $conversations = $this->queryService
            ->forOrganization($request->user(), $request)
            ->paginate(40)
            ->withQueryString();

        $queueStats = $this->inboxQueueStats($request->user());

        return view('dashboard.inbox.archive', compact('conversations', 'queueStats'));
    }

    public function queue(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        $awaiting = Conversation::query()
            ->with(['website', 'assignedUser', 'department'])
            ->whereHas('website', fn ($q) => $q->where('organization_id', $orgId))
            ->where('status', 'awaiting_agent')
            ->visibleInInbox()
            ->orderBy('sla_due_at')
            ->get();

        $agents = $this->assignment->availableAgents($orgId);
        $queueStats = $this->inboxQueueStats($request->user());

        return view('dashboard.inbox.queue', compact('awaiting', 'agents', 'queueStats'));
    }

    /** @return array{awaiting: int, open: int, mine: int} */
    protected function inboxQueueStats(User $user): array
    {
        return [
            'awaiting' => Conversation::query()
                ->whereHas('website', fn ($q) => $q->where('organization_id', $user->organization_id))
                ->where('status', 'awaiting_agent')
                ->visibleInInbox()
                ->count(),
            'open' => Conversation::query()
                ->whereHas('website', fn ($q) => $q->where('organization_id', $user->organization_id))
                ->whereIn('status', ['open', 'pending', 'waiting_visitor'])
                ->visibleInInbox()
                ->count(),
            'mine' => Conversation::query()
                ->where('assigned_user_id', $user->id)
                ->whereIn('status', ['open', 'awaiting_agent', 'pending', 'waiting_visitor'])
                ->count(),
        ];
    }

    public function poll(Request $request): JsonResponse
    {
        $conversations = $this->queryService
            ->forOrganization($request->user(), $request)
            ->with('latestMessage')
            ->limit(20)
            ->get()
            ->map(fn (Conversation $c) => [
                'id' => $c->id,
                'website' => $c->website?->name,
                'website_id' => $c->website_id,
                'visitor_name' => $c->visitor_name,
                'status' => $c->status,
                'mode' => $c->mode,
                'priority' => $c->priority,
                'is_starred' => $c->is_starred,
                'is_pinned' => $c->is_pinned,
                'agent_unread_count' => $c->agent_unread_count,
                'last_message_at' => $c->last_message_at?->diffForHumans(null, true),
                'preview' => \Illuminate\Support\Str::limit($c->latestMessage?->content ?: ($c->latestMessage ? '📎 Attachment' : ''), 72),
                'url' => route('inbox.index', ['conversation' => $c->id]),
                'awaiting' => $c->status === 'awaiting_agent',
            ]);

        return response()->json([
            'count' => $conversations->count(),
            'awaiting' => $conversations->where('awaiting', true)->count(),
            'queue_stats' => $this->inboxQueueStats($request->user()),
            'conversations' => $conversations,
        ]);
    }

    public function bulk(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureCanWrite($request);
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:conversations,id',
            'action' => 'required|in:assign,close,tag,star,unstar,pin,unpin,priority',
            'priority' => 'nullable|in:low,normal,medium,high,urgent',
            'tag' => 'nullable|string|max:64',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $updated = 0;

        foreach ($validated['ids'] as $id) {
            $conversation = Conversation::find($id);
            if (! $conversation || ! $this->canAccess($conversation)) {
                continue;
            }

            match ($validated['action']) {
                'assign' => $this->bulkAssign($conversation, $request->user()),
                'close' => $this->bulkClose($conversation, $request->user()),
                'star' => $conversation->update(['is_starred' => true]),
                'unstar' => $conversation->update(['is_starred' => false]),
                'pin' => $conversation->update(['is_pinned' => true]),
                'unpin' => $conversation->update(['is_pinned' => false]),
                'priority' => $conversation->update(['priority' => $validated['priority'] ?? 'normal']),
                'tag' => $this->bulkTag($conversation, $validated['tag'] ?? ''),
                default => null,
            };

            $updated++;
        }

        if ($request->wantsJson()) {
            return response()->json(['updated' => $updated]);
        }

        return back()->with('success', "{$updated} conversation(s) updated.");
    }

    public function updateMeta(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        abort_unless($this->canAccess($conversation), 403);
        $this->ensureCanWrite($request);

        foreach (['is_starred', 'is_pinned'] as $boolField) {
            if ($request->has($boolField)) {
                $request->merge([
                    $boolField => filter_var($request->input($boolField), FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        }

        if ($request->has('department_id') && $request->input('department_id') === '') {
            $request->merge(['department_id' => null]);
        }

        $validated = $request->validate([
            'is_starred' => 'sometimes|boolean',
            'is_pinned' => 'sometimes|boolean',
            'priority' => 'sometimes|in:low,normal,medium,high,urgent',
            'status' => 'sometimes|in:open,pending,waiting_visitor,resolved,closed',
            'snoozed_until' => 'nullable|date',
            'assigned_user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'agent_draft' => 'nullable|string|max:4000',
        ]);

        if (array_key_exists('department_id', $validated) && $validated['department_id']) {
            abort_unless(
                Department::where('id', $validated['department_id'])
                    ->where('organization_id', $request->user()->organization_id)
                    ->exists(),
                403
            );
        }

        $conversation->update($validated);
        broadcast(new ConversationUpdated($conversation->fresh(), 'meta_updated'));

        return $request->wantsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', 'Conversation updated.');
    }

    public function assign(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        abort_unless($this->canAccess($conversation), 403);
        $this->ensureCanWrite($request);
        $conversation->update(['assigned_user_id' => $request->user()->id, 'status' => 'open']);
        ChatEvent::log($conversation, 'assigned', $request->user());
        broadcast(new ConversationUpdated($conversation->fresh(), 'assigned'));

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Conversation assigned to you.']);
        }

        $redirect = $request->input('redirect');
        if (is_string($redirect) && str_starts_with($redirect, url('/'))) {
            return redirect($redirect)->with('success', 'Conversation assigned to you.');
        }

        return back()->with('success', 'Conversation assigned to you.');
    }

    public function transfer(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        abort_unless($this->canAccess($conversation), 403);
        $this->ensureCanWrite($request);

        $validated = $request->validate(['user_id' => 'required|exists:users,id']);
        $agent = User::findOrFail($validated['user_id']);

        $this->assignment->transfer($conversation, $agent, $request->user());
        broadcast(new ConversationUpdated($conversation->fresh(), 'transferred'));

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => "Transferred to {$agent->name}."]);
        }

        return back()->with('success', "Transferred to {$agent->name}.");
    }

    public function storeNote(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        abort_unless($this->canAccess($conversation), 403);
        $this->ensureCanWriteInbox($request);

        $validated = $request->validate(['body' => 'required|string|max:5000']);

        $note = ConversationNote::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'is_internal' => true,
        ]);

        $this->mentions->syncNoteMentions($note, $conversation, $request->user());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'note' => [
                    'id' => $note->id,
                    'body_html' => $this->mentions->formatBodyHtml($note->body),
                    'author' => $request->user()->name,
                ],
            ]);
        }

        return back()->with('success', 'Internal note saved.');
    }

    public function saveAsLead(Conversation $conversation, LeadCaptureService $leads, Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($this->canAccess($conversation), 403);
        $this->ensureCanWrite($request);
        $lead = $leads->captureFromConversation($conversation->website, $conversation->fresh());

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Lead saved to CRM.',
                'lead' => [
                    'id' => $lead->id,
                    'name' => $lead->name ?: 'Visitor',
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'status' => ucfirst($lead->status),
                    'url' => route('leads.show', $lead),
                ],
            ]);
        }

        return back()->with('success', 'Lead saved from conversation.');
    }

    public function updateContact(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        abort_unless($this->canAccess($conversation), 403);
        $this->ensureCanWrite($request);

        $validated = $request->validate([
            'visitor_name' => 'required|string|max:120',
            'visitor_email' => 'nullable|email|max:255',
            'visitor_phone' => 'required|string|max:40',
            'visitor_company' => 'nullable|string|max:255',
        ]);

        $conversation->update($validated);
        broadcast(new ConversationUpdated($conversation->fresh(), 'contact_updated'));

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Contact saved.',
                'contact' => [
                    'name' => $conversation->visitor_name,
                    'email' => $conversation->visitor_email,
                    'phone' => $conversation->visitor_phone,
                    'company' => $conversation->visitor_company,
                ],
            ]);
        }

        return back()->with('success', 'Contact updated.');
    }

    public function close(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        abort_unless($this->canAccess($conversation), 403);
        $this->ensureCanWrite($request);
        $conversation->update(['status' => 'closed', 'closed_at' => now()]);
        ChatEvent::log($conversation, 'closed', auth()->user());
        broadcast(new ConversationUpdated($conversation->fresh(), 'closed'));

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Conversation closed.']);
        }

        return back()->with('success', 'Conversation closed.');
    }

    public function reopen(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        abort_unless($this->canAccess($conversation), 403);
        $this->ensureCanWrite($request);
        $conversation->update(['status' => 'open', 'closed_at' => null, 'resolved_at' => null, 'last_message_at' => now()]);
        ChatEvent::log($conversation, 'reopened', auth()->user());
        broadcast(new ConversationUpdated($conversation->fresh(), 'reopened'));

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Conversation reopened.']);
        }

        return back()->with('success', 'Conversation reopened.');
    }

    public function resolve(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        abort_unless($this->canAccess($conversation), 403);
        $this->ensureCanWrite($request);
        $conversation->update(['status' => 'resolved', 'resolved_at' => now()]);
        ChatEvent::log($conversation, 'resolved', auth()->user());
        broadcast(new ConversationUpdated($conversation->fresh(), 'resolved'));

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Conversation marked resolved.']);
        }

        return back()->with('success', 'Conversation marked resolved.');
    }

    public function returnToAi(Request $request, Conversation $conversation, LiveHandoffService $handoff): RedirectResponse|JsonResponse
    {
        abort_unless($this->canAccess($conversation), 403);
        $this->ensureCanWrite($request);
        $handoff->returnToAi($conversation);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'AI has resumed this conversation.']);
        }

        return back()->with('success', 'AI has resumed this conversation.');
    }

    protected function canAccess(Conversation $conversation): bool
    {
        return $conversation->website->organization_id === auth()->user()->organization_id;
    }

    protected function ensureCanWrite(Request $request): void
    {
        $this->ensureCanWriteInbox($request);
    }

    protected function bulkAssign(Conversation $conversation, User $user): void
    {
        $conversation->update(['assigned_user_id' => $user->id, 'status' => 'open']);
        ChatEvent::log($conversation, 'bulk_assigned', $user);
    }

    protected function bulkClose(Conversation $conversation, User $user): void
    {
        $conversation->update(['status' => 'closed', 'closed_at' => now()]);
        ChatEvent::log($conversation, 'bulk_closed', $user);
    }

    protected function bulkTag(Conversation $conversation, string $tag): void
    {
        if ($tag === '') {
            return;
        }

        $tags = $conversation->tags ?? [];
        if (! in_array($tag, $tags, true)) {
            $tags[] = $tag;
            $conversation->update(['tags' => $tags]);
        }
    }
}
