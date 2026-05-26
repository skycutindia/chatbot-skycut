<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Join {{ $organization->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/framework.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-theme.css') }}">
</head>
<body class="auth-page">
<div class="auth-card-wrap">
    <div class="auth-brand">
        <div class="dash-logo-icon auth-logo-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="3"/></svg>
        </div>
        <div>
            <h1 class="auth-title">Join {{ $organization->name }}</h1>
            <p class="auth-subtitle">You were invited as {{ \App\Enums\UserRole::from($invite->role)->label() }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('team.invite.accept', $invite->token) }}" class="auth-form dash-card">
        @csrf
        <p class="text-sm dash-muted mb-4">Signing in as <strong>{{ $invite->email }}</strong></p>

        @if($needsAccount)
            <div class="auth-field">
                <label class="auth-label">Your name</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="dash-input auth-input">
                @error('name')<p class="auth-error">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label class="auth-label">Password</label>
                <input type="password" name="password" required class="dash-input auth-input">
                @error('password')<p class="auth-error">{{ $message }}</p>@enderror
            </div>
            <div class="auth-field">
                <label class="auth-label">Confirm password</label>
                <input type="password" name="password_confirmation" required class="dash-input auth-input">
            </div>
        @else
            <div class="auth-field">
                <label class="auth-label">Password</label>
                <input type="password" name="password" required class="dash-input auth-input" placeholder="Your existing account password">
                @error('password')<p class="auth-error">{{ $message }}</p>@enderror
            </div>
            @if($existingName)
                <p class="text-sm dash-muted">Welcome back, {{ $existingName }}.</p>
            @endif
        @endif

        <button type="submit" class="dash-btn-primary auth-submit">Accept invite</button>
        <p class="auth-links"><a href="{{ route('login') }}">Sign in instead</a></p>
    </form>
</div>
</body>
</html>
