@extends('layouts.app')

@section('title', $organization->name)

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Platform admin</p>
        <h1 class="dash-page-title">{{ $organization->name }}</h1>
        <p class="dash-page-sub">{{ $organization->slug }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-medium">
    <a href="{{ route('admin.organizations.index') }}" class="dash-back-link">← Organizations</a>

    <form method="POST" action="{{ route('admin.organizations.update', $organization) }}" class="dash-card mt-8 max-w-lg">
        <div class="dash-card-body space-y-4">
            @csrf @method('PATCH')
            <div class="dash-field">
                <label class="dash-label" for="name">Name</label>
                <input id="name" name="name" value="{{ $organization->name }}" class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="timezone">Timezone</label>
                <input id="timezone" name="timezone" value="{{ $organization->timezone }}" class="dash-input w-full">
            </div>
            <label class="dash-checkbox-row">
                <input type="checkbox" name="is_active" value="1" @checked($organization->is_active)>
                Active
            </label>
            <button type="submit" class="dash-btn-primary dash-btn-sm">Save</button>
        </div>
    </form>

    <div class="grid md:grid-cols-2 gap-6 mt-8">
        <div class="dash-card">
            <div class="dash-card-header">
                <h2 class="font-semibold">Websites ({{ $organization->websites->count() }})</h2>
            </div>
            <ul class="dash-list">
                @foreach($organization->websites as $site)
                    <li class="dash-list-item text-sm">
                        <span>{{ $site->name }}</span>
                        <span class="dash-muted">{{ $site->configuration?->bot_name }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="dash-card">
            <div class="dash-card-header">
                <h2 class="font-semibold">Team ({{ $organization->users->count() }})</h2>
            </div>
            <ul class="dash-list">
                @foreach($organization->users as $user)
                    <li class="dash-list-item text-sm">
                        <span>{{ $user->name }}</span>
                        <span class="dash-muted">{{ $user->role }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
