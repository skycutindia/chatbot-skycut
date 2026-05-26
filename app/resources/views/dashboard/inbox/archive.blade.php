@extends('layouts.app')

@section('title', 'Chat Archive')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/live-chat.css') }}?v={{ filemtime(public_path('css/live-chat.css')) }}">
@endpush

@push('scripts')
<script src="{{ asset('js/live-chat.js') }}?v={{ filemtime(public_path('js/live-chat.js')) }}"></script>
@endpush

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Live chat</p>
        <h1 class="dash-page-title">Archive</h1>
    </div>
</div>
@endsection

@section('page-toolbar')
@include('dashboard.inbox.partials.inbox-toolbar')
@endsection

@section('content')
@include('dashboard.inbox.partials.inbox-config')

<div class="lc-sub-page">
    <form method="GET" class="lc-archive-search">
        <svg class="lc-search-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3-3"/></svg>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search archive…" class="lc-archive-search-input" autocomplete="off">
        <button type="submit" class="lc-btn lc-btn-primary">Search</button>
    </form>

    <div class="lc-archive-list">
        @forelse($conversations as $conversation)
            @php
                $initials = collect(explode(' ', trim($conversation->visitor_name ?: 'Visitor')))
                    ->filter()->take(2)
                    ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                    ->join('') ?: 'V';
            @endphp
            <a href="{{ route('inbox.index', ['conversation' => $conversation->id]) }}" class="lc-archive-item">
                <span class="lc-archive-avatar">{{ $initials }}</span>
                <span class="lc-archive-body">
                    <span class="lc-archive-top">
                        <span class="lc-archive-name">{{ $conversation->visitor_name ?: 'Visitor' }}</span>
                        <span class="lc-archive-time">{{ $conversation->closed_at?->diffForHumans(null, true) ?? $conversation->resolved_at?->diffForHumans(null, true) }}</span>
                    </span>
                    @if($conversation->visitor_email)<span class="lc-archive-email">{{ $conversation->visitor_email }}</span>@endif
                    <span class="lc-archive-meta">
                        <span>{{ $conversation->website?->name }}</span>
                        <span class="lc-pill">{{ str_replace('_', ' ', $conversation->status) }}</span>
                    </span>
                </span>
                <svg class="lc-archive-chevron" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        @empty
            <div class="lc-empty lc-empty-block">
                <div class="lc-empty-icon">📁</div>
                <p class="lc-empty-title">No archived conversations</p>
                <p class="lc-empty-sub">Closed chats will appear here for search and reopen.</p>
            </div>
        @endforelse
    </div>

    @if($conversations->hasPages())
        <div class="lc-pagination">{{ $conversations->links() }}</div>
    @endif
</div>
@endsection
