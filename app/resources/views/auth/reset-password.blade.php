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
    <h1 class="text-3xl font-bold text-white">Set new password</h1>
    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-4 bg-slate-900 border border-slate-800 rounded-2xl p-8">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label class="block text-sm text-slate-400 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $email) }}" required class="w-full rounded-lg bg-slate-800 border border-slate-700 px-4 py-2 text-white">
            @error('email')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm text-slate-400 mb-1">New password</label>
            <input type="password" name="password" required class="w-full rounded-lg bg-slate-800 border border-slate-700 px-4 py-2 text-white">
        </div>
        <div>
            <label class="block text-sm text-slate-400 mb-1">Confirm password</label>
            <input type="password" name="password_confirmation" required class="w-full rounded-lg bg-slate-800 border border-slate-700 px-4 py-2 text-white">
        </div>
        <button class="w-full py-3 rounded-lg bg-indigo-600 font-medium text-white">Reset password</button>
    </form>
</div>
</body>
</html>
