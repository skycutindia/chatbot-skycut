<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register — AI Chatbot Hub Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center p-6">
<div class="w-full max-w-md">
    <h1 class="text-3xl font-bold text-white">Create account</h1>
    <p class="text-slate-400 mt-2">Manage unlimited AI chatbots from one dashboard</p>
    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4 bg-slate-900 border border-slate-800 rounded-2xl p-8">
        @csrf
        <div>
            <label class="block text-sm text-slate-400 mb-1">Company name</label>
            <input type="text" name="company_name" value="{{ old('company_name') }}" required class="w-full rounded-lg bg-slate-800 border border-slate-700 px-4 py-2 text-white outline-none focus:ring-2 focus:ring-indigo-500">
            @error('company_name')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm text-slate-400 mb-1">Your name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-lg bg-slate-800 border border-slate-700 px-4 py-2 text-white outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm text-slate-400 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg bg-slate-800 border border-slate-700 px-4 py-2 text-white outline-none focus:ring-2 focus:ring-indigo-500">
            @error('email')<p class="text-red-400 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm text-slate-400 mb-1">Password</label>
            <input type="password" name="password" required class="w-full rounded-lg bg-slate-800 border border-slate-700 px-4 py-2 text-white outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm text-slate-400 mb-1">Confirm password</label>
            <input type="password" name="password_confirmation" required class="w-full rounded-lg bg-slate-800 border border-slate-700 px-4 py-2 text-white outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit" class="w-full py-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-medium text-white">Create account</button>

        @include('auth.partials.social-buttons')

        <p class="text-center text-sm text-slate-500"><a href="{{ route('login') }}" class="text-indigo-400">Already have an account?</a></p>
    </form>
</div>
</body>
</html>
