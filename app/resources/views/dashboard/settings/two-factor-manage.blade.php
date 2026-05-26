@extends('layouts.app')

@section('title', 'Two-factor authentication')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Settings</p>
        <h1 class="dash-page-title">Two-factor authentication</h1>
        <p class="dash-page-sub">Your account is protected with an authenticator app</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow">

    <div class="mt-8 space-y-6">
        <div class="dash-alert-success">
            Two-factor authentication is active since {{ $user->two_factor_confirmed_at->format('M j, Y') }}.
        </div>

        <div class="dash-card">
            <div class="dash-card-body">
                <h2 class="dash-form-section-title">Recovery codes</h2>
                <p class="text-sm dash-muted mt-1">Use recovery codes if you lose access to your authenticator app.</p>
                <a href="{{ route('settings.two-factor.recovery-codes') }}" class="dash-btn-secondary dash-btn-sm mt-4 inline-flex">
                    View / regenerate recovery codes
                </a>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-card-body">
                <h2 class="dash-form-section-title text-red-600">Disable two-factor authentication</h2>
                <p class="text-sm dash-muted mt-1">You will need your password and a current authentication code.</p>
                <form method="POST" action="{{ route('settings.two-factor.disable') }}" class="mt-4 space-y-4">
                    @csrf
                    @method('DELETE')
                    <div class="dash-field">
                        <label class="dash-label" for="password">Password</label>
                        <input type="password" id="password" name="password" required class="dash-input w-full max-w-sm">
                        @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="dash-field">
                        <label class="dash-label" for="code">Authentication code</label>
                        <input type="text" id="code" name="code" inputmode="numeric" required class="dash-input w-full max-w-xs">
                        @error('code')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="dash-btn-danger dash-btn-sm">
                        Disable two-factor authentication
                    </button>
                </form>
            </div>
        </div>
    </div>

    <p class="mt-6">
        <a href="{{ route('settings.profile.edit') }}" class="dash-back-link">← Back to profile</a>
    </p>
</div>
@endsection
