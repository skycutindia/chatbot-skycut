@extends('layouts.app')

@section('title', 'My profile')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Settings</p>
        <h1 class="dash-page-title">My profile</h1>
        <p class="dash-page-sub">Update your account details and password</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow">

    @if(!$user->hasVerifiedEmail())
        <div class="dash-alert-warning mt-6">
            Your email is not verified.
            <a href="{{ route('verification.notice') }}" class="dash-link ml-1">Verify now</a>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.profile.update') }}" class="dash-card mt-8">
        <div class="dash-card-body space-y-6">
            @csrf
            @method('PUT')

            <div class="dash-field">
                <label class="dash-label" for="name">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="dash-input w-full">
                @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="dash-field">
                <label class="dash-label" for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="dash-input w-full">
                @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="dash-form-section pt-4 border-t border-[var(--dash-border)]">
                <h2 class="dash-form-section-title">Change password</h2>
                <div class="space-y-4 mt-4">
                    <div class="dash-field">
                        <label class="dash-label" for="current_password">Current password</label>
                        <input type="password" id="current_password" name="current_password" class="dash-input w-full">
                        @error('current_password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="dash-field">
                        <label class="dash-label" for="password">New password</label>
                        <input type="password" id="password" name="password" class="dash-input w-full">
                        @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="dash-field">
                        <label class="dash-label" for="password_confirmation">Confirm new password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="dash-input w-full">
                    </div>
                </div>
            </div>

            <button type="submit" class="dash-btn-primary">Save profile</button>
        </div>
    </form>

    <div class="dash-card mt-8">
        <div class="dash-card-body">
            <h2 class="dash-form-section-title">Connected accounts</h2>
            @if($user->socialAccounts->isEmpty())
                <p class="text-sm dash-muted mt-2">No social accounts linked. Connect Google or GitHub from the sign-in page.</p>
            @else
                <ul class="dash-list mt-4">
                    @foreach($user->socialAccounts as $account)
                        <li class="dash-list-item">
                            <span class="dash-badge dash-badge-muted capitalize">{{ $account->provider }}</span>
                            <span class="dash-muted">{{ $account->provider_email ?? 'Linked' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="dash-card mt-8">
        <div class="dash-card-body">
            <h2 class="dash-form-section-title">Two-factor authentication</h2>
            <p class="text-sm dash-muted mt-2">
                @if($user->hasTwoFactorEnabled())
                    Enabled — your account requires a code from your authenticator app when signing in.
                @else
                    Not enabled — protect your account with an authenticator app.
                @endif
            </p>
            @if($user->hasTwoFactorEnabled())
                <a href="{{ route('settings.two-factor.show') }}" class="dash-btn-secondary dash-btn-sm mt-4 inline-flex">Manage two-factor authentication</a>
            @else
                <a href="{{ route('settings.two-factor.show') }}" class="dash-btn-primary dash-btn-sm mt-4 inline-flex">Enable two-factor authentication</a>
            @endif
        </div>
    </div>
</div>
@endsection
