@extends('layouts.app')

@section('title', 'Organizations')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Platform admin</p>
        <h1 class="dash-page-title">Organizations</h1>
        <p class="dash-page-sub">All tenant accounts on the platform</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page">

    <div class="dash-table-wrap mt-6">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Organization</th>
                    <th>Websites</th>
                    <th>Users</th>
                    <th>Leads</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($organizations as $org)
                    <tr>
                        <td><a href="{{ route('admin.organizations.show', $org) }}" class="dash-link font-medium">{{ $org->name }}</a></td>
                        <td class="dash-muted">{{ $org->websites_count }}</td>
                        <td class="dash-muted">{{ $org->users_count }}</td>
                        <td class="dash-muted">{{ $org->leads_count }}</td>
                        <td>
                            <span class="dash-badge {{ $org->is_active ? 'dash-badge-success' : 'dash-badge-muted' }}">{{ $org->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $organizations->links() }}</div>
</div>
@endsection
