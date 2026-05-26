@extends('layouts.app')

@section('title', 'Conversation')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Conversation</p>
        <h1 class="dash-page-title">{{ $conversation->visitor_name ?? 'Visitor' }}</h1>
    </div>
</div>
@endsection

@section('page-toolbar')
<div class="flex gap-2 flex-wrap items-center">
    <span class="text-xs dash-muted">{{ $conversation->visitor_email }} · <span id="conv-status">{{ $conversation->status }}</span> · {{ $conversation->mode }} mode</span>
    @if($conversation->status !== 'closed')
        <form method="POST" action="{{ route('websites.conversations.close', [$website, $conversation]) }}">@csrf<button type="submit" class="dash-btn-danger dash-btn-sm">Close</button></form>
    @else
        <form method="POST" action="{{ route('websites.conversations.reopen', [$website, $conversation]) }}">@csrf<button type="submit" class="dash-btn-secondary dash-btn-sm">Reopen</button></form>
    @endif
    @if($conversation->mode === 'human')
        <form method="POST" action="{{ route('websites.conversations.return-to-ai', [$website, $conversation]) }}">@csrf<button type="submit" class="dash-btn-secondary dash-btn-sm">Return to AI</button></form>
    @endif
</div>
@endsection

@section('content')
<div class="dash-page-medium">
    <a href="{{ route('websites.conversations.index', $website) }}" class="dash-back-link">← Back to conversations</a>

    <div class="dash-card mt-4 overflow-hidden flex flex-col" style="height: calc(100vh - 200px); min-height: 520px">
        <div id="message-list" class="flex-1 overflow-y-auto p-5 space-y-4 bg-gradient-to-b from-teal-50/50 to-[var(--dash-bg)]">
            @foreach($conversation->messages as $msg)
                <div class="flex {{ $msg->sender_type === 'visitor' ? 'justify-end' : 'justify-start' }}" data-msg-id="{{ $msg->id }}">
                    <div class="flex items-end gap-2 max-w-[80%] {{ $msg->sender_type === 'visitor' ? 'flex-row-reverse' : '' }}">
                        <div class="w-8 h-8 rounded-full shrink-0 flex items-center justify-center text-xs {{ $msg->sender_type === 'visitor' ? 'bg-slate-200 text-slate-600' : ($msg->sender_type === 'agent' ? 'bg-emerald-100 text-emerald-700' : 'bg-[var(--brand-50)] text-[var(--brand)]') }}">
                            @if($msg->sender_type === 'visitor') 👤 @elseif($msg->sender_type === 'agent') 🎧 @else 🤖 @endif
                        </div>
                        <div class="rounded-2xl px-4 py-3 text-sm {{ $msg->sender_type === 'visitor' ? 'bg-teal-600 text-white rounded-br-md' : ($msg->sender_type === 'agent' ? 'bg-emerald-50 text-emerald-900 border border-emerald-200 rounded-bl-md' : 'bg-white border border-slate-200 rounded-bl-md shadow-sm') }}">
                            {{ $msg->content }}
                            <p class="text-xs opacity-60 mt-1">{{ $msg->created_at->format('H:i') }} · {{ $msg->source }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($conversation->status !== 'closed')
            @if($canned->isNotEmpty())
                <div class="px-5 py-2 border-t border-[var(--dash-border)] flex flex-wrap gap-2 bg-[var(--dash-surface)]">
                    @foreach($canned as $c)
                        <button type="button" class="text-xs px-3 py-1.5 rounded-full border border-[var(--dash-border)] hover:border-[var(--brand)] hover:text-[var(--brand)] transition" data-body="{{ e($c->body) }}" onclick="document.getElementById('reply-input').value=this.dataset.body">{{ $c->title }}</button>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('websites.conversations.reply', [$website, $conversation]) }}" class="p-4 border-t border-[var(--dash-border)] bg-[var(--dash-surface)]" id="reply-form">
                @csrf
                <div class="flex gap-2 items-center bg-[var(--dash-surface-2)] border border-[var(--dash-border)] rounded-full pl-5 pr-2 py-2 focus-within:border-[var(--brand)] focus-within:ring-2 focus-within:ring-teal-500/20">
                    <input name="content" id="reply-input" placeholder="Type a message..." required class="flex-1 bg-transparent border-none outline-none text-sm dash-input">
                    <button type="submit" class="w-10 h-10 rounded-full bg-gradient-to-br from-teal-600 to-teal-400 text-white flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
