@extends('layouts.app')

@section('title', 'Add team member')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Team</p>
        <h1 class="dash-page-title">Add team member</h1>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow">
    <a href="{{ route('team.index') }}" class="dash-back-link">← Team</a>

    <form method="POST" action="{{ route('team.store') }}" class="dash-card mt-6">
        <div class="dash-card-body space-y-4">
            @csrf
            <div class="dash-field">
                <label class="dash-label" for="name">Full name</label>
                <input id="name" name="name" placeholder="Full name" required class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="email">Email</label>
                <input id="email" type="email" name="email" placeholder="Email" required class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="Password" required class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm password" required class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="role">Role</label>
                <select id="role" name="role" class="dash-select w-full">
                    @foreach($roles as $role)
                        <option value="{{ $role }}">{{ \App\Enums\UserRole::from($role)->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="dash-btn-primary w-full">Create account</button>
        </div>
    </form>
</div>
@endsection
