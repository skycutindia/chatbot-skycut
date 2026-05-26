<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invite unavailable</title>
    <link rel="stylesheet" href="{{ asset('css/framework.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-theme.css') }}">
</head>
<body class="auth-page">
<div class="auth-card-wrap">
    <div class="dash-card auth-form">
        <h1 class="auth-title">Invite unavailable</h1>
        <p class="text-sm dash-muted mt-2">{{ $message ?? 'This invite link is invalid or has expired. Ask your admin to send a new invite.' }}</p>
        <p class="auth-links mt-6"><a href="{{ route('login') }}">Go to sign in</a></p>
    </div>
</div>
</body>
</html>
