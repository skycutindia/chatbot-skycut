@extends('layouts.app')

@section('title', 'Recovery codes')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Settings</p>
        <h1 class="dash-page-title">Recovery codes</h1>
        <p class="dash-page-sub">Store these codes in a safe place. Each code can only be used once.</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow">

    @if($codes)
        <div class="dash-alert-warning mt-8">
            Copy these codes now. They will not be shown again unless you regenerate them.
        </div>

        <ul class="mt-6 grid gap-2 sm:grid-cols-2 font-mono text-sm dash-card p-6">
            @foreach($codes as $code)
                <li class="px-3 py-2 rounded bg-[var(--dash-surface-2)]">{{ $code }}</li>
            @endforeach
        </ul>
    @else
        <div class="dash-card mt-8">
            <div class="dash-card-body">
                <p class="text-sm dash-muted">Recovery codes are hidden for security. Regenerate new codes if you need a fresh set.</p>
                <form method="POST" action="{{ route('settings.two-factor.recovery-codes.regenerate') }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="dash-field">
                        <label class="dash-label" for="password">Confirm your password</label>
                        <input type="password" id="password" name="password" required class="dash-input w-full max-w-xs">
                        @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="dash-btn-primary dash-btn-sm">
                        Generate new recovery codes
                    </button>
                </form>
            </div>
        </div>
    @endif

    <p class="mt-6">
        <a href="{{ route('settings.two-factor.show') }}" class="dash-back-link">← Back to two-factor settings</a>
    </p>
</div>
@endsection
