@extends('layouts.app')

@section('title', 'Live Chat Analytics')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Live chat</p>
        <h1 class="dash-page-title">Chat Analytics</h1>
    </div>
</div>
@endsection

@section('page-toolbar')
<div class="flex gap-2 flex-wrap">
    <a href="{{ route('live-chat.analytics', ['days' => 7]) }}" class="dash-btn-secondary dash-btn-sm @if($days === 7) dash-btn-active @endif">7d</a>
    <a href="{{ route('live-chat.analytics', ['days' => 30]) }}" class="dash-btn-secondary dash-btn-sm @if($days === 30) dash-btn-active @endif">30d</a>
    <a href="{{ route('live-chat.analytics.export', ['days' => $days]) }}" class="dash-btn-secondary dash-btn-sm">Export CSV</a>
    <a href="{{ route('inbox.index') }}" class="dash-btn-primary dash-btn-sm">Open inbox</a>
</div>
@endsection

@section('content')
<div class="dash-page-medium">
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
        <div class="dash-stat">
            <p class="dash-stat-label">Total chats</p>
            <p class="dash-stat-value">{{ $summary['total_chats'] ?? 0 }}</p>
            <p class="dash-stat-meta">{{ $summary['active_chats'] ?? 0 }} active now</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Awaiting agent</p>
            <p class="dash-stat-value">{{ $summary['awaiting_agent'] ?? 0 }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Handoff rate</p>
            <p class="dash-stat-value">{{ $summary['handoff_rate'] ?? 0 }}%</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Resolution rate</p>
            <p class="dash-stat-value">{{ $summary['resolution_rate'] ?? 0 }}%</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Avg first response</p>
            <p class="dash-stat-value">{{ $summary['avg_first_response_minutes'] !== null ? $summary['avg_first_response_minutes'].'m' : '—' }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Avg resolution time</p>
            <p class="dash-stat-value">{{ $summary['avg_resolution_minutes'] !== null ? $summary['avg_resolution_minutes'].'m' : '—' }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Avg satisfaction</p>
            <p class="dash-stat-value">{{ $summary['avg_satisfaction'] !== null ? $summary['avg_satisfaction'].'/5' : '—' }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">File attachments</p>
            <p class="dash-stat-value">{{ $summary['attachments'] ?? 0 }}</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mt-8">
        <div class="dash-card">
            <h2 class="dash-card-title">Chats per day</h2>
            <div class="dash-chart-bars mt-4">
                @foreach($chart as $bar)
                    <div class="dash-chart-bar-col">
                        <div class="dash-chart-bar" style="height: {{ max($bar['height_pct'], 4) }}%"></div>
                        <span class="dash-chart-label">{{ $bar['label'] }}</span>
                        <span class="dash-chart-count">{{ $bar['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="dash-card">
            <h2 class="dash-card-title">Status breakdown</h2>
            <div class="mt-4 space-y-3">
                @forelse($statusBreakdown as $row)
                    <div class="flex justify-between items-center text-sm">
                        <span class="capitalize">{{ str_replace('_', ' ', $row['status']) }}</span>
                        <span class="font-semibold">{{ $row['total'] }}</span>
                    </div>
                @empty
                    <p class="text-sm dash-muted">No conversations yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="dash-card mt-8">
        <h2 class="dash-card-title">Agent performance</h2>
        <div class="dash-table-wrap mt-4">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Assigned chats</th>
                        <th>Messages sent</th>
                        <th>Avg rating</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $agent)
                        <tr>
                            <td>{{ $agent['name'] }}</td>
                            <td>{{ $agent['assigned'] }}</td>
                            <td>{{ $agent['messages'] }}</td>
                            <td>{{ $agent['avg_rating'] !== null ? $agent['avg_rating'].'/5' : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="dash-muted">No agent activity in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
