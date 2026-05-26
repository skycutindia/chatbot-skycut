@extends('layouts.app')

@section('title', 'Leads')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Lead CRM</p>
        <h1 class="dash-page-title">Lead CRM</h1>
    </div>
</div>
@endsection

@section('page-toolbar')
<a href="{{ route('leads.export') }}" class="dash-btn-secondary dash-btn-sm">Export CSV</a>
@endsection

@section('content')
<div class="dash-page">

    <form method="GET" class="dash-filter-bar mt-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, company..." class="dash-input flex-1 min-w-[200px]">
        <select name="status" class="dash-select">
            <option value="">All statuses</option>
            @foreach($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <button type="submit" class="dash-btn-primary dash-btn-sm">Filter</button>
    </form>

    <div class="dash-table-wrap mt-6">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Website</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td>
                            <a href="{{ route('leads.show', $lead) }}" class="dash-link font-medium">
                                {{ $lead->name ?: $lead->email ?: 'Anonymous' }}
                            </a>
                            <p class="dash-muted text-xs mt-0.5">{{ $lead->email }}</p>
                        </td>
                        <td class="dash-muted">{{ $lead->website?->name }}</td>
                        <td><span class="dash-badge dash-badge-muted">{{ $lead->statusEnum()->label() }}</span></td>
                        <td class="dash-muted">{{ $lead->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="dash-empty">No leads yet. They appear when visitors share contact info in chat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $leads->links() }}</div>
</div>
@endsection
