@extends('layouts.websites-workspace')

@section('title', 'Analytics — '.$website->name)

@section('ws-tab', 'analytics')
@section('ws-title', 'Analytics')
@section('ws-subtitle', 'Conversations, leads, handoffs, and AI performance for this site.')

@section('page-toolbar')
<a href="{{ route('websites.analytics.export', $website) }}" class="dash-btn-secondary dash-btn-sm">Export CSV</a>
@endsection

@section('workspace')
    <div class="ws-stats-grid">
        <div class="dash-stat">
            <p class="dash-stat-label">Conversations</p>
            <p class="dash-stat-value">{{ $summary['conversations'] }}</p>
            <p class="dash-stat-meta">{{ $summary['open_conversations'] }} open now</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Leads</p>
            <p class="dash-stat-value">{{ $summary['leads'] }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Handoff rate</p>
            <p class="dash-stat-value">{{ $summary['handoff_rate'] }}%</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">AI resolution</p>
            <p class="dash-stat-value">{{ $summary['ai_resolution_rate'] }}%</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Messages</p>
            <p class="dash-stat-value">{{ $summary['messages'] }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Widget opens</p>
            <p class="dash-stat-value">{{ $summary['widget_opens'] }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Widget loads</p>
            <p class="dash-stat-value">{{ $summary['widget_loads'] }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Avg satisfaction</p>
            <p class="dash-stat-value">{{ $summary['avg_satisfaction'] ? $summary['avg_satisfaction'].'/5' : '—' }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Human handoffs</p>
            <p class="dash-stat-value">{{ $summary['handoffs'] }}</p>
        </div>
    </div>

    @if($summary['events']->isNotEmpty())
        <div class="dash-card mt-8">
            <div class="dash-card-header">
                <h2 class="font-semibold">Events breakdown</h2>
            </div>
            <div class="dash-card-body">
                <ul class="dash-list">
                    @foreach($summary['events'] as $type => $count)
                        <li class="dash-list-item">
                            <span>{{ $type }}</span>
                            <span class="font-semibold">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
@endsection
