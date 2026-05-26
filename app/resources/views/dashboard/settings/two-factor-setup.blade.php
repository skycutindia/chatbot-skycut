@extends('layouts.app')

@section('title', 'Set up two-factor authentication')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Settings</p>
        <h1 class="dash-page-title">Set up two-factor authentication</h1>
        <p class="dash-page-sub">Scan the QR code, then enter the 6-digit code to confirm</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow">

    <div class="mt-8 grid gap-6 md:grid-cols-2">
        <div class="dash-card">
            <div class="dash-card-body flex flex-col items-center justify-center">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrUri) }}"
                     alt="QR code for authenticator app" width="200" height="200" class="rounded-lg bg-white p-2">
                <p class="text-xs dash-muted mt-4 text-center">Scan with your authenticator app</p>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-card-body">
                <p class="text-sm dash-muted">Or enter this key manually:</p>
                <p class="mt-2 font-mono text-sm break-all bg-[var(--dash-surface-2)] rounded-lg px-3 py-2 select-all">{{ $secret }}</p>

                <form method="POST" action="{{ route('settings.two-factor.confirm') }}" class="mt-6 space-y-4">
                    @csrf
                    <div class="dash-field">
                        <label class="dash-label" for="code">6-digit code</label>
                        <input type="text" id="code" name="code" inputmode="numeric" maxlength="6" required autofocus
                               placeholder="000000"
                               class="dash-input w-full text-center text-lg tracking-widest">
                        @error('code')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="dash-btn-primary w-full">
                        Confirm and enable
                    </button>
                </form>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.two-factor.cancel') }}" class="mt-6">
        @csrf
        <button type="submit" class="dash-link text-sm">Cancel setup</button>
    </form>
</div>
@endsection
