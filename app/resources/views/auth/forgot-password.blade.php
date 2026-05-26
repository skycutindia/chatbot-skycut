<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password — AI Chatbot Hub Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center p-6">
<div class="w-full max-w-md">
    <h1 class="text-3xl font-bold text-white">Reset password</h1>
    <p class="text-slate-400 mt-2">We'll email you a reset link</p>
    @if(session('success'))
        <div class="mt-6 rounded-lg bg-emerald-500/10 border border-emerald-500/30 px-4 py-3 text-emerald-300 text-sm">{{ session('success') }}</div>
    @endif
    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-4 bg-slate-900 border border-slate-800 rounded-2xl p-8">
        @csrf
        <div>
            <label class="block text-sm text-slate-400 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg bg-slate-800 border border-slate-700 px-4 py-2 text-white outline-none focus:ring-2 focus:ring-indigo-500">
            @error('email')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="w-full py-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-medium text-white">Send reset link</button>
        <p class="text-center text-sm"><a href="{{ route('login') }}" class="text-indigo-400">Back to sign in</a></p>
    </form>
</div>
</body>
</html>
