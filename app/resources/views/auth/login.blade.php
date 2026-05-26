<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — SkyCut</title>
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
            <h1 class="auth-title">SkyCut</h1>
            <p class="auth-subtitle">Admin dashboard</p>
        </div>
    </div>

    @if(session('success'))
        <div class="dash-alert-success mb-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form dash-card">
        @csrf
        <div class="auth-field">
            <label class="auth-label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="dash-input auth-input">
            @error('email')<p class="auth-error">{{ $message }}</p>@enderror
        </div>
        <div class="auth-field">
            <label class="auth-label">Password</label>
            <input type="password" name="password" required class="dash-input auth-input">
        </div>
        <button type="submit" class="dash-btn-primary auth-submit">Sign in to dashboard</button>

        @include('auth.partials.social-buttons')

        <p class="auth-links">
            <a href="{{ route('password.request') }}">Forgot password?</a>
            ·
            <a href="{{ route('register') }}">Create account</a>
        </p>
    </form>

    <p class="auth-demo">
        Demo: <code>admin@aichatbothub.local</code> / <code>password</code>
    </p>
</div>
</body>
</html>
