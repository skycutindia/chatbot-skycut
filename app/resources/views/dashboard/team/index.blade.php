@extends('layouts.app')

@section('title', 'Team')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Team</p>
        <h1 class="dash-page-title">Team</h1>
    </div>
</div>
@endsection

@section('page-toolbar')
<a href="{{ route('team.create') }}" class="dash-btn-primary dash-btn-sm">+ Add member</a>
@endsection

@section('content')
<div class="dash-page-medium">

    <div class="dash-table-wrap mt-6">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($members as $member)
                    <tr>
                        <td class="font-medium">{{ $member->name }}</td>
                        <td class="dash-muted">{{ $member->email }}</td>
                        <td><span class="dash-badge dash-badge-muted">{{ $member->roleEnum()->label() }}</span></td>
                        <td>{{ $member->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="text-right">
                            <a href="{{ route('team.edit', $member) }}" class="dash-link text-sm">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $members->links() }}</div>
</div>
@endsection
