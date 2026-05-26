@extends('layouts.app')

@section('title', 'Queue Monitor')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/live-chat.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/live-chat.js') }}?v={{ filemtime(public_path('js/live-chat.js')) }}"></script>
@endpush

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Live chat</p>
        <h1 class="dash-page-title">Queue</h1>
    </div>
</div>
@endsection

@section('page-toolbar')
@include('dashboard.inbox.partials.inbox-toolbar')
@endsection

@section('content')
@include('dashboard.inbox.partials.inbox-config')
<div class="dash-page lc-sub-page">
    <div class="lc-sub-stats">
        <div class="lc-sub-stat">
            <span class="lc-sub-stat-icon lc-sub-stat-icon-wait" aria-hidden="true">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div>
                <p class="lc-sub-stat-label">Waiting</p>
                <p class="lc-sub-stat-value">{{ $awaiting->count() }}</p>
            </div>
        </div>
        <div class="lc-sub-stat">
            <span class="lc-sub-stat-icon lc-sub-stat-icon-agents" aria-hidden="true">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8m12 4v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            </span>
            <div>
                <p class="lc-sub-stat-label">Available agents</p>
                <p class="lc-sub-stat-value">{{ $agents->count() }}</p>
            </div>
        </div>
        <div class="lc-sub-stat">
            <span class="lc-sub-stat-icon lc-sub-stat-icon-eta" aria-hidden="true">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </span>
            <div>
                <p class="lc-sub-stat-label">Est. wait</p>
                <p class="lc-sub-stat-value">{{ $agents->count() > 0 ? ceil($awaiting->count() / max($agents->count(), 1)) * 2 : '—' }}<span class="lc-sub-stat-unit"> min</span></p>
            </div>
        </div>
    </div>

    <div class="lc-queue-list">
        @forelse($awaiting as $i => $conversation)
            @php
                $initials = collect(explode(' ', trim($conversation->visitor_name ?: 'Visitor')))
                    ->filter()->take(2)
                    ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                    ->join('') ?: 'V';
                $slaBreached = $conversation->sla_due_at && $conversation->sla_due_at->isPast();
            @endphp
            <div class="lc-queue-card {{ $slaBreached ? 'is-sla-breach' : '' }}">
                <div class="lc-queue-card-rank">#{{ $i + 1 }}</div>
                <span class="lc-queue-card-avatar" aria-hidden="true">{{ $initials }}</span>
                <div class="lc-queue-card-body">
                    <p class="lc-queue-card-name">{{ $conversation->visitor_name ?: 'Visitor' }}</p>
                    <p class="lc-queue-card-meta">
                        {{ $conversation->website?->name }}
                        @if($conversation->department) · {{ $conversation->department->name }}@endif
                    </p>
                    <div class="lc-queue-card-tags">
                        <span class="lc-status-pill is-awaiting">Awaiting agent</span>
                        <span class="lc-queue-wait">Waiting {{ $conversation->last_message_at?->diffForHumans() }}</span>
                        @if($conversation->sla_due_at)
                            <span class="lc-queue-sla {{ $slaBreached ? 'is-breach' : '' }}">
                                SLA {{ $conversation->sla_due_at->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                </div>
                @if(auth()->user()->roleEnum()->canHandleLiveChat())
                <form method="POST" action="{{ route('inbox.assign', $conversation) }}" class="lc-queue-take-form">
                    @csrf
                    <input type="hidden" name="redirect" value="{{ route('inbox.index', ['conversation' => $conversation->id]) }}">
                    <button type="submit" class="lc-btn lc-btn-primary lc-queue-take">Take chat</button>
                </form>
                @else
                <a href="{{ route('inbox.index', ['conversation' => $conversation->id]) }}" class="lc-btn lc-btn-secondary lc-queue-take">View chat</a>
                @endif
            </div>
        @empty
            <div class="lc-sub-empty">
                <div class="lc-sub-empty-icon" aria-hidden="true">
                    <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="lc-sub-empty-title">Queue is clear</p>
                <p class="lc-sub-empty-sub">No visitors waiting — you're all caught up.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
