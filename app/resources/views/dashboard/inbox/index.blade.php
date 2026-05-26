@extends('layouts.app')

@section('title', 'Live Inbox')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/live-chat.css') }}?v={{ filemtime(public_path('css/live-chat.css')) }}">
<link rel="stylesheet" href="{{ asset('css/emoji-picker.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/emoji-picker.js') }}"></script>
<script src="{{ asset('js/mention-autocomplete.js') }}"></script>
<script src="{{ asset('js/live-chat.js') }}?v={{ filemtime(public_path('js/live-chat.js')) }}"></script>
@if($active && $activeWebsite)
<script>
window.__CHATBOT_REALTIME__ = Object.assign(window.__CHATBOT_REALTIME__ || {}, {
    page: 'inbox',
    conversationId: @json($active->id),
    messagesUrl: @json(route('websites.conversations.messages', [$activeWebsite, $active])),
});
</script>
@endif
@endpush

@section('page-header')
@include('layouts.partials.page-header', ['eyebrow' => 'Live chat', 'title' => 'Inbox'])
@endsection

@section('content')
@php
    $filtersActive = request()->filled('website_id') || request()->filled('department_id') || request('sort', 'newest') !== 'newest' || request('awaiting') || request('starred') || request('pinned') || request('assigned');
@endphp
@include('dashboard.inbox.partials.inbox-config')

<div class="lc-inbox-page">
    <div class="lc-unified-inbox {{ ($active ?? null) ? 'lc-has-chat' : '' }}">

        {{-- Left: Customer conversations --}}
        <aside class="lc-pane lc-pane-list" aria-label="Customer conversations">
            <header class="lc-pane-head">
                <div class="lc-pane-head-main">
                    <span class="lc-pane-icon" aria-hidden="true">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-1M13 8V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l4-4h4"/></svg>
                    </span>
                    <div>
                        <h2 class="lc-pane-title">Conversations</h2>
                        <p class="lc-pane-sub">All customer chats</p>
                    </div>
                </div>
                <span class="lc-pane-badge" id="lc-list-count">{{ $conversations->total() }}</span>
                <button type="button" class="lc-btn lc-btn-sm lc-btn-ghost" id="lc-enable-notify" title="Browser notifications when tab is in background">Notify</button>
            </header>

            <div class="lc-pane-body lc-list-pane">
                <div class="lc-list-toolbar">
                    <form method="GET" id="lc-filter-form" class="lc-filter-form">
                        <div class="lc-list-toolbar-row">
                            @if(auth()->user()->roleEnum()->canHandleLiveChat())
                                <label class="lc-list-recent" title="Select all">
                                    <input type="checkbox" id="lc-select-all" aria-label="Select all conversations">
                                    <span class="lc-list-recent-label">Recent</span>
                                </label>
                            @else
                                <span class="lc-list-recent lc-list-recent-static">
                                    <span class="lc-list-recent-label">Recent</span>
                                </span>
                            @endif
                            <div class="lc-search-bar">
                                <svg class="lc-search-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3-3"/></svg>
                                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email, phone…" class="lc-filter-search" autocomplete="off">
                                <button type="button" id="lc-filter-toggle" class="lc-filter-btn {{ $filtersActive ? 'is-active' : '' }}" aria-expanded="false" aria-controls="lc-filter-panel" title="Filters">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M7 12h10M10 18h4"/></svg>
                                    @if($filtersActive)<span class="lc-filter-badge"></span>@endif
                                </button>
                            </div>
                        </div>
                        <div id="lc-filter-panel" class="lc-filter-panel" hidden>
                            <select name="view" class="lc-select" data-auto-filter>
                                @php
                                    $view = request('assigned') === 'me' ? 'assigned' : (request('awaiting') ? 'awaiting' : (request('starred') ? 'starred' : (request('pinned') ? 'pinned' : 'all')));
                                @endphp
                                <option value="all" @selected($view === 'all')>All conversations</option>
                                <option value="awaiting" @selected($view === 'awaiting')>Awaiting agent</option>
                                <option value="assigned" @selected($view === 'assigned')>Assigned to me</option>
                                <option value="starred" @selected($view === 'starred')>Starred</option>
                                <option value="pinned" @selected($view === 'pinned')>Pinned</option>
                            </select>
                            <select name="website_id" class="lc-select" data-auto-filter>
                                <option value="">All websites</option>
                                @foreach($websites as $site)
                                    <option value="{{ $site->id }}" @selected(request('website_id') == $site->id)>{{ $site->name }}</option>
                                @endforeach
                            </select>
                            <select name="department_id" class="lc-select" data-auto-filter>
                                <option value="">All departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            <select name="sort" class="lc-select" data-auto-filter>
                                <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest first</option>
                                <option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option>
                                <option value="priority" @selected(request('sort') === 'priority')>By priority</option>
                            </select>
                            <div class="lc-filter-presets" id="lc-filter-presets">
                                <select id="lc-preset-select" class="lc-select" title="Saved views">
                                    <option value="">Saved views…</option>
                                </select>
                                <button type="button" class="lc-btn lc-btn-sm lc-btn-ghost" id="lc-preset-save" title="Save current filters">Save view</button>
                            </div>
                            @if($filtersActive)
                                <a href="{{ route('inbox.index') }}" class="lc-filter-clear">Clear filters</a>
                            @endif
                            <a href="{{ route('inbox.export', request()->query()) }}" class="lc-btn lc-btn-sm lc-btn-secondary">Export CSV</a>
                        </div>
                    </form>
                </div>

                @if(auth()->user()->roleEnum()->canHandleLiveChat())
                    <div id="lc-bulk-bar" class="lc-bulk-bar">
                        <span id="lc-bulk-count" class="lc-bulk-count">0 selected</span>
                        <button type="button" class="lc-btn lc-btn-sm lc-btn-ghost" data-lc-bulk="assign">Assign me</button>
                        <button type="button" class="lc-btn lc-btn-sm lc-btn-ghost" data-lc-bulk="star">Star</button>
                        <button type="button" class="lc-btn lc-btn-sm lc-btn-ghost" data-lc-bulk="pin">Pin</button>
                        <button type="button" class="lc-btn lc-btn-sm lc-btn-danger" data-lc-bulk="close">Close</button>
                    </div>
                @endif

                <div class="lc-list-scroll" id="lc-list-scroll">
                    @forelse($conversations as $conversation)
                        @php
                            $initials = collect(explode(' ', trim($conversation->visitor_name ?: 'Visitor')))
                                ->filter()->take(2)
                                ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                                ->join('') ?: 'V';
                            $preview = $conversation->latestMessage?->content
                                ?: ($conversation->latestMessage?->source === 'attachment' ? '📎 Attachment' : null);
                        @endphp
                        <div class="lc-item-wrap {{ ($active?->id ?? null) === $conversation->id ? 'is-active' : '' }}" data-conversation-id="{{ $conversation->id }}">
                            @if(auth()->user()->roleEnum()->canHandleLiveChat())
                                <label class="lc-item-check-wrap">
                                    <input type="checkbox" class="lc-row-check" value="{{ $conversation->id }}">
                                </label>
                            @endif
                            <a href="{{ route('inbox.index', array_merge(request()->query(), ['conversation' => $conversation->id])) }}" class="lc-item">
                                <span class="lc-item-avatar">{{ $initials }}</span>
                                <span class="lc-item-body">
                                    <span class="lc-item-top">
                                        <span class="lc-item-name">
                                            @if($conversation->is_pinned)<span class="lc-pin">📌</span>@endif
                                            @if($conversation->is_starred)<span class="lc-star">★</span>@endif
                                            @if($conversation->channel === 'whatsapp')<span class="lc-wa">WA</span>@endif
                                            {{ $conversation->visitor_name ?: 'Visitor' }}
                                        </span>
                                        <span class="lc-item-time">{{ $conversation->last_message_at?->diffForHumans(null, true) }}</span>
                                    </span>
                                    @if($preview)<span class="lc-item-preview">{{ \Illuminate\Support\Str::limit($preview, 72) }}</span>@endif
                                    <span class="lc-item-footer">
                                        <span class="lc-item-site">{{ $conversation->website?->name }}</span>
                                        <span class="lc-pill {{ $conversation->status === 'awaiting_agent' ? 'is-warn' : '' }}">{{ str_replace('_', ' ', $conversation->status) }}</span>
                                        @if($conversation->priority !== 'normal')<span class="lc-pill is-accent">{{ $conversation->priority }}</span>@endif
                                    </span>
                                </span>
                                @if($conversation->agent_unread_count > 0)
                                    <span class="lc-unread" data-unread>{{ $conversation->agent_unread_count }}</span>
                                @else
                                    <span class="lc-unread" data-unread hidden>0</span>
                                @endif
                            </a>
                        </div>
                    @empty
                        <div class="lc-empty">
                            <div class="lc-empty-icon">💬</div>
                            <p class="lc-empty-title">No conversations</p>
                            <p class="lc-empty-sub">Adjust filters or check the queue for waiting visitors.</p>
                        </div>
                    @endforelse
                </div>

                @if($conversations->hasPages())
                    <div class="lc-pagination">{{ $conversations->links() }}</div>
                @endif
            </div>
        </aside>

        {{-- Right: Chat + visitor details --}}
        <main class="lc-pane lc-pane-chat" aria-label="Active conversation">
            @if($active && $activeWebsite)
                @include('dashboard.inbox.partials.chat-panel', [
                    'conversation' => $active,
                    'website' => $activeWebsite,
                    'canned' => $canned,
                    'agentQuickReplies' => $agentQuickReplies,
                    'agents' => $agents,
                    'departments' => $departments,
                ])
            @else
                <div class="lc-chat-empty">
                    <div class="lc-empty-card">
                        <div class="lc-empty-icon-lg">💬</div>
                        <p class="lc-empty-title">No chat selected</p>
                        <p class="lc-empty-sub">Pick a customer from the list to view messages and contact details.</p>
                    </div>
                </div>
            @endif
        </main>

    </div>
</div>
@endsection
