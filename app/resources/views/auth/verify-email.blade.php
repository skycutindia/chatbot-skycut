<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify email — AI Chatbot Hub Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center p-6">
<div class="w-full max-w-md text-center">
    <h1 class="text-2xl font-bold text-white">Verify your email</h1>
    <p class="text-slate-400 mt-3 text-sm">
        We sent a verification link to <strong class="text-slate-200">{{ auth()->user()->email }}</strong>.
        Click the link in that email to access your dashboard.
    </p>

    @if(session('success'))
        <div class="mt-6 rounded-lg bg-emerald-500/10 border border-emerald-500/30 px-4 py-3 text-emerald-300 text-sm">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-8">
        @csrf
        <button type="submit" class="px-6 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-medium text-white transition">
            Resend verification email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm text-slate-500 hover:text-slate-300">Sign out</button>
    </form>
</div>
</body>
</html>
