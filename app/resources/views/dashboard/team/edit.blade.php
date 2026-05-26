@extends('layouts.app')

@section('title', 'Edit '.$member->name)

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Team</p>
        <h1 class="dash-page-title">Edit {{ $member->name }}</h1>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow">
    <a href="{{ route('team.index') }}" class="dash-back-link">← Team</a>

    <form method="POST" action="{{ route('team.update', $member) }}" class="dash-card mt-6">
        <div class="dash-card-body space-y-4">
            @csrf @method('PATCH')
            <div class="dash-field">
                <label class="dash-label" for="name">Name</label>
                <input id="name" name="name" value="{{ $member->name }}" required class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ $member->email }}" required class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="role">Role</label>
                <select id="role" name="role" class="dash-select w-full">
                    @foreach($roles as $role)
                        <option value="{{ $role }}" @selected($member->role === $role)>{{ \App\Enums\UserRole::from($role)->label() }}</option>
                    @endforeach
                </select>
            </div>
            <label class="dash-checkbox-row">
                <input type="checkbox" name="is_active" value="1" @checked($member->is_active)>
                Active
            </label>
            <div class="dash-field">
                <label class="dash-label" for="password">New password (optional)</label>
                <input id="password" type="password" name="password" placeholder="New password (optional)" class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm new password" class="dash-input w-full">
            </div>
            <button type="submit" class="dash-btn-primary w-full">Save changes</button>
        </div>
    </form>
</div>
@endsection
