@extends('layouts.app')

@section('title', 'Reports')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Reports</p>
        <h1 class="dash-page-title">Reports</h1>
    </div>
</div>
@endsection

@section('page-toolbar')
<div class="flex flex-wrap gap-2 items-center">
    <div class="dash-segments">
        <a href="{{ route('reports.index', ['days' => 7]) }}" class="dash-segment {{ $days === 7 ? 'dash-segment-active' : '' }}">7d</a>
        <a href="{{ route('reports.index', ['days' => 30]) }}" class="dash-segment {{ $days === 30 ? 'dash-segment-active' : '' }}">30d</a>
        <a href="{{ route('reports.index', ['days' => 90]) }}" class="dash-segment {{ $days === 90 ? 'dash-segment-active' : '' }}">90d</a>
    </div>
    <a href="{{ route('reports.export', ['days' => $days, 'format' => 'csv']) }}" class="dash-btn-secondary dash-btn-sm">CSV</a>
    <a href="{{ route('reports.export', ['days' => $days, 'format' => 'excel']) }}" class="dash-btn-secondary dash-btn-sm">Excel</a>
    <a href="{{ route('reports.export', ['days' => $days, 'format' => 'pdf']) }}" class="dash-btn-secondary dash-btn-sm">PDF</a>
</div>
@endsection

@section('content')
<div class="dash-page">

    <div class="grid md:grid-cols-4 gap-4 mt-8">
        <div class="dash-stat">
            <p class="dash-stat-label">Conversations</p>
            <p class="dash-stat-value">{{ $totals['conversations'] }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Leads</p>
            <p class="dash-stat-value">{{ $totals['leads'] }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Handoffs</p>
            <p class="dash-stat-value">{{ $totals['handoffs'] }}</p>
        </div>
        <div class="dash-stat">
            <p class="dash-stat-label">Widget opens</p>
            <p class="dash-stat-value">{{ $totals['widget_opens'] }}</p>
        </div>
    </div>

    <div class="dash-table-wrap mt-8">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Website</th>
                    <th>Chats</th>
                    <th>Leads</th>
                    <th>Handoff %</th>
                    <th>AI %</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summaries as $row)
                    <tr>
                        <td><a href="{{ route('websites.analytics', $row['website']) }}" class="dash-link">{{ $row['website']->name }}</a></td>
                        <td>{{ $row['conversations'] }}</td>
                        <td>{{ $row['leads'] }}</td>
                        <td>{{ $row['handoff_rate'] }}%</td>
                        <td>{{ $row['ai_resolution_rate'] }}%</td>
                        <td>{{ $row['avg_satisfaction'] ? $row['avg_satisfaction'].'/5' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
