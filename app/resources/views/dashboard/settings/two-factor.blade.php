@extends('layouts.app')

@section('title', 'Two-factor authentication')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Settings</p>
        <h1 class="dash-page-title">Two-factor authentication</h1>
        <p class="dash-page-sub">Add an extra layer of security to your account</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow">

    <div class="dash-card mt-8">
        <div class="dash-card-body">
            <div class="flex items-start gap-4">
                <div class="dash-stat-icon blue text-xl">🔐</div>
                <div class="flex-1">
                    <h2 class="dash-form-section-title">Authenticator app</h2>
                    <p class="text-sm dash-muted mt-1">
                        Use Google Authenticator, Authy, 1Password, or any TOTP-compatible app.
                    </p>
                    @if($user->hasTwoFactorEnabled())
                        <p class="mt-3 text-sm dash-badge dash-badge-success inline-flex">Two-factor authentication is enabled.</p>
                    @else
                        <form method="POST" action="{{ route('settings.two-factor.enable') }}" class="mt-4 space-y-3">
                            @csrf
                            <div class="dash-field">
                                <label class="dash-label" for="password">Confirm your password to continue</label>
                                <input type="password" id="password" name="password" required class="dash-input w-full max-w-xs">
                                @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="dash-btn-primary dash-btn-sm">
                                Enable two-factor authentication
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <p class="mt-4">
        <a href="{{ route('settings.profile.edit') }}" class="dash-back-link">← Back to profile</a>
    </p>
</div>
@endsection
