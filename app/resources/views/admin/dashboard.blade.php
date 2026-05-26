@extends('layouts.app')

@section('title', 'Platform Admin')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Platform admin</p>
        <h1 class="dash-page-title">Platform Administration</h1>
        <p class="dash-page-sub">Manage all tenants, users, and system settings</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page">

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-8">
        @foreach([
            ['label' => 'Organizations', 'value' => $stats['organizations']],
            ['label' => 'Users', 'value' => $stats['users']],
            ['label' => 'Websites', 'value' => $stats['websites']],
            ['label' => 'Conversations', 'value' => $stats['conversations']],
            ['label' => 'Open chats', 'value' => $stats['open_chats']],
            ['label' => 'Leads', 'value' => $stats['leads']],
        ] as $stat)
            <div class="dash-stat">
                <p class="dash-stat-label">{{ $stat['label'] }}</p>
                <p class="dash-stat-value">{{ number_format($stat['value']) }}</p>
            </div>
        @endforeach
    </div>

    @if(!empty($chartData))
        <div class="dash-card mt-8">
            <div class="dash-card-body">
            <h2 class="font-bold mb-4">Platform conversation activity (7 days)</h2>
            <div class="flex items-end justify-between gap-3 h-32">
                @foreach($chartData as $day)
                    <div class="flex-1 flex flex-col items-center gap-2">
                        <span class="text-xs font-semibold">{{ $day['count'] }}</span>
                        <div class="w-full flex items-end justify-center" style="height: 80px">
                            <div class="dash-chart-bar w-full max-w-[32px]" style="height: {{ max($day['height_pct'], 4) }}%"></div>
                        </div>
                        <span class="text-xs dash-muted">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
            </div>
        </div>
    @endif

    <div class="mt-8 flex gap-3">
        <a href="{{ route('admin.organizations.index') }}" class="dash-btn-primary">All organizations</a>
        <a href="{{ route('admin.settings.edit') }}" class="dash-btn-secondary">Platform settings</a>
    </div>

    <div class="dash-card mt-10">
        <div class="dash-card-header">
            <h2 class="font-bold">Recent organizations</h2>
        </div>
        <div class="overflow-hidden">
        @foreach($recentOrganizations as $org)
            <a href="{{ route('admin.organizations.show', $org) }}" class="dash-table-row">
                <div>
                    <p class="font-medium">{{ $org->name }}</p>
                    <p class="text-sm dash-muted">{{ $org->websites_count }} websites · {{ $org->users_count }} users</p>
                </div>
                <span class="dash-badge {{ $org->is_active ? 'dash-badge-success' : 'dash-badge-muted' }}">{{ $org->is_active ? 'Active' : 'Inactive' }}</span>
            </a>
        @endforeach
        </div>
    </div>
</div>
@endsection
