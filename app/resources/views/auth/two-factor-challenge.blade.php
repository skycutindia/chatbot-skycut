<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-factor authentication — AI Chatbot Hub Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center p-6">
<div class="w-full max-w-md">
    <h1 class="text-2xl font-bold text-white">Two-factor authentication</h1>
    <p class="text-slate-400 mt-2 text-sm">Enter the 6-digit code from your authenticator app, or use a recovery code.</p>

    <form method="POST" action="{{ route('two-factor.verify') }}" class="mt-8 space-y-4 bg-slate-900 border border-slate-800 rounded-2xl p-8">
        @csrf
        <div>
            <label class="block text-sm text-slate-400 mb-1">Authentication code</label>
            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus
                   placeholder="000000"
                   class="w-full rounded-lg bg-slate-800 border border-slate-700 px-4 py-2 text-white text-center text-lg tracking-widest focus:ring-2 focus:ring-indigo-500 outline-none">
            @error('code')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="w-full py-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-medium text-white transition">
            Verify
        </button>
    </form>

    <p class="text-center text-sm text-slate-500 mt-6">
        <a href="{{ route('login') }}" class="text-indigo-400 hover:text-indigo-300">Back to sign in</a>
    </p>
</div>
</body>
</html>
