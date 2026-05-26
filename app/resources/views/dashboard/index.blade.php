@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Overview</p>
        <h1 class="dash-page-title">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}</h1>
    </div>
</div>
@endsection

@section('page-toolbar')
<div class="flex flex-wrap gap-3">
    @if(auth()->user()->roleEnum()->canHandleLiveChat())
        <a href="{{ route('inbox.index') }}" class="dash-btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Live Inbox
            @if($stats['awaiting_agent'] > 0)
                <span class="bg-white/20 px-2 py-0.5 rounded-full text-xs">{{ $stats['awaiting_agent'] }}</span>
            @endif
        </a>
    @endif
</div>
@endsection

@section('content')
<div class="dash-page">

    {{-- Today --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="dash-stat">
            <p class="dash-stat-label">Chats today</p>
            <p class="dash-stat-value">{{ number_format($todayStats['chats_today']) }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Leads today</p>
            <p class="dash-stat-value">{{ number_format($todayStats['leads_today']) }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Avg CSAT today</p>
            <p class="dash-stat-value">{{ $todayStats['avg_csat'] ? $todayStats['avg_csat'].'/5' : '—' }}</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="dash-stat">
            <div class="flex items-start justify-between">
                <div>
                    <p class="dash-stat-label">Total conversations</p>
                    <p class="dash-stat-value">{{ number_format($stats['conversations']) }}</p>
                    <p class="dash-stat-meta">{{ $stats['messages_30d'] }} messages (30d)</p>
                </div>
                <div class="dash-stat-icon teal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
            </div>
        </div>
        <div class="dash-stat">
            <div class="flex items-start justify-between">
                <div>
                    <p class="dash-stat-label">Open chats</p>
                    <p class="dash-stat-value">{{ $stats['open_chats'] }}</p>
                    <p class="text-xs mt-1">
                        @if($stats['awaiting_agent'] > 0)
                            <span class="text-amber-600 font-medium">{{ $stats['awaiting_agent'] }} awaiting agent</span>
                        @else
                            <span class="dash-muted">All caught up</span>
                        @endif
                    </p>
                </div>
                <div class="dash-stat-icon amber">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="dash-stat">
            <div class="flex items-start justify-between">
                <div>
                    <p class="dash-stat-label">Leads captured</p>
                    <p class="dash-stat-value">{{ number_format($stats['leads']) }}</p>
                    <p class="dash-stat-meta">CRM pipeline</p>
                </div>
                <div class="dash-stat-icon blue">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="dash-stat">
            <div class="flex items-start justify-between">
                <div>
                    <p class="dash-stat-label">Satisfaction</p>
                    <p class="dash-stat-value">{{ $stats['satisfaction'] ? $stats['satisfaction'].'/5' : '—' }}</p>
                    <p class="text-xs text-green-600 font-medium mt-1">{{ $stats['satisfaction'] ? '98.5% positive' : 'No ratings yet' }}</p>
                </div>
                <div class="dash-stat-icon green">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mb-8">
        {{-- Chart --}}
        <div class="lg:col-span-2 dash-card p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="font-bold text-lg">Conversation activity</h2>
                    <p class="text-sm dash-muted">Last 7 days</p>
                </div>
                @if($primaryWebsite)
                    <a href="{{ route('websites.analytics', $primaryWebsite) }}" class="text-sm font-medium text-[var(--brand)] hover:underline">View analytics</a>
                @endif
            </div>
            <div class="flex items-end justify-between gap-3 h-40 px-2">
                @foreach($chartData as $day)
                    <div class="flex-1 flex flex-col items-center gap-2">
                        <span class="text-xs font-semibold">{{ $day['count'] }}</span>
                        <div class="w-full flex items-end justify-center" style="height: 120px">
                            <div class="dash-chart-bar w-full max-w-[40px]" style="height: {{ max($day['height_pct'], 4) }}%"></div>
                        </div>
                        <span class="text-xs dash-muted">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Performance --}}
        <div class="dash-card p-6">
            <h2 class="font-bold text-lg mb-6">AI performance</h2>
            <div class="space-y-5">
                <div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="dash-muted">AI resolution rate</span>
                        <span class="font-semibold">{{ $stats['ai_resolution'] }}%</span>
                    </div>
                    <div class="dash-progress"><div class="dash-progress-fill" style="width: {{ $stats['ai_resolution'] }}%"></div></div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="dash-muted">Handoff rate</span>
                        <span class="font-semibold">{{ $stats['handoff_rate'] }}%</span>
                    </div>
                    <div class="dash-progress"><div class="dash-progress-fill" style="width: {{ min($stats['handoff_rate'], 100) }}%; opacity: 0.7"></div></div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="dash-muted">Active bots</span>
                        <span class="font-semibold">{{ $stats['active_bots'] }}/{{ $stats['websites'] }}</span>
                    </div>
                    <div class="dash-progress"><div class="dash-progress-fill" style="width: {{ $stats['websites'] > 0 ? round(($stats['active_bots'] / $stats['websites']) * 100) : 0 }}%"></div></div>
                </div>
            </div>
            @if($primaryWebsite)
                <a href="{{ route('websites.index') }}" class="dash-btn-primary w-full justify-center mt-6">Manage websites</a>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-8">
        {{-- Recent conversations --}}
        <div class="dash-card overflow-hidden">
            <div class="px-6 py-4 border-b border-[var(--dash-border)] flex justify-between items-center">
                <h2 class="font-bold">Recent conversations</h2>
                @if($primaryWebsite)
                    <a href="{{ route('websites.conversations.index', $primaryWebsite) }}" class="text-sm text-[var(--brand)] hover:underline">View all</a>
                @endif
            </div>
            @forelse($recentConversations as $conversation)
                <a href="{{ route('websites.conversations.show', [$conversation->website, $conversation]) }}" class="dash-table-row">
                    <div>
                        <p class="font-medium text-sm">{{ $conversation->visitor_name ?: 'Visitor' }}</p>
                        <p class="text-xs dash-muted">{{ $conversation->website?->name }} · {{ $conversation->last_message_at?->diffForHumans() }}</p>
                    </div>
                    <span class="dash-badge {{ $conversation->status === 'awaiting_agent' ? 'dash-badge-warning' : 'dash-badge-muted' }}">{{ $conversation->status }}</span>
                </a>
            @empty
                <p class="p-6 text-sm dash-muted text-center">No conversations yet — embed the widget on your site to start receiving chats.</p>
            @endforelse
        </div>

        {{-- Awaiting agent queue --}}
        <div class="dash-card overflow-hidden">
            <div class="px-6 py-4 border-b border-[var(--dash-border)] flex justify-between items-center">
                <h2 class="font-bold">Awaiting agent</h2>
                <a href="{{ route('inbox.index') }}" class="text-sm text-[var(--brand)] hover:underline">Open inbox</a>
            </div>
            @forelse($awaitingQueue as $conversation)
                <a href="{{ route('websites.conversations.show', [$conversation->website, $conversation]) }}" class="dash-table-row">
                    <div>
                        <p class="font-medium text-sm">{{ $conversation->visitor_name ?: 'Visitor' }}</p>
                        <p class="text-xs dash-muted">{{ $conversation->website?->name }}</p>
                    </div>
                    <span class="dash-btn-primary text-xs py-1.5 px-3">Reply</span>
                </a>
            @empty
                <p class="p-6 text-sm dash-muted text-center">No chats waiting — you're all caught up!</p>
            @endforelse
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="mb-8">
        <h2 class="font-bold text-lg mb-4">Quick actions</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @if(auth()->user()->roleEnum()->canHandleLiveChat())
                <a href="{{ route('inbox.index') }}" class="dash-quick-action">
                    <strong>Live Inbox</strong>
                    <span>Answer waiting visitors</span>
                </a>
            @endif
            @if($primaryWebsite)
                <a href="{{ route('websites.knowledge.index', $primaryWebsite) }}" class="dash-quick-action">
                    <strong>Knowledge base</strong>
                    <span>Train FAQ & articles</span>
                </a>
                <a href="{{ route('websites.embed', $primaryWebsite) }}" class="dash-quick-action">
                    <strong>Embed widget</strong>
                    <span>Copy install snippet</span>
                </a>
                <a href="{{ route('websites.analytics', $primaryWebsite) }}" class="dash-quick-action">
                    <strong>Analytics</strong>
                    <span>Performance reports</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Websites --}}
    <div>
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold text-lg">Your websites</h2>
            @if(auth()->user()->roleEnum()->canManageWebsites())
                <a href="{{ route('websites.create') }}" class="dash-btn-secondary text-sm">+ New website</a>
            @endif
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($websites as $site)
                <a href="{{ route('websites.edit', $site) }}" class="dash-card p-5 dash-card-hover transition block">
                    <div class="flex justify-between items-start">
                        <div class="dash-logo-icon w-10 h-10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="3"/></svg>
                        </div>
                        <span class="dash-badge {{ $site->is_active ? 'dash-badge-success' : 'dash-badge-muted' }}">{{ $site->is_active ? 'Active' : 'Off' }}</span>
                    </div>
                    <h3 class="font-bold mt-4">{{ $site->name }}</h3>
                    <p class="text-sm dash-muted mt-1">{{ $site->configuration?->bot_name ?? 'Assistant' }}</p>
                </a>
            @empty
                <p class="dash-muted col-span-full text-center py-8">No websites yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
