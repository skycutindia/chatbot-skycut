@extends('layouts.app')

@section('title', 'WhatsApp Business')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Integrations</p>
        <h1 class="dash-page-title">WhatsApp Business</h1>
        <p class="dash-page-sub">Connect Meta Cloud API to receive and reply from the live inbox</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow">

    <div class="dash-card mt-8">
        <div class="dash-card-body space-y-4">
            <div class="dash-form-section-title">Webhook URL</div>
            <p class="text-sm dash-muted">In Meta Developer Console → WhatsApp → Configuration, set this callback URL and use the verify token below.</p>
            <div class="flex flex-wrap items-center gap-2">
                <code class="text-xs bg-[var(--dash-surface-2)] px-3 py-2 rounded-lg break-all">{{ $webhookUrl }}</code>
            </div>
            @if($channel)
                <div class="dash-field">
                    <label class="dash-label">Verify token</label>
                    <input type="text" readonly value="{{ $channel->verify_token }}" class="dash-input w-full font-mono text-sm">
                </div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('settings.whatsapp.update') }}" class="dash-card mt-6">
        <div class="dash-card-body space-y-6">
            @csrf
            @method('PUT')

            <div class="dash-field">
                <label class="dash-label" for="phone_number_id">Phone number ID</label>
                <input type="text" id="phone_number_id" name="phone_number_id" value="{{ old('phone_number_id', $channel->phone_number_id ?? '') }}" required class="dash-input w-full" placeholder="From Meta WhatsApp API setup">
                @error('phone_number_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="dash-field">
                <label class="dash-label" for="display_phone">Display phone (optional)</label>
                <input type="text" id="display_phone" name="display_phone" value="{{ old('display_phone', $channel->display_phone ?? '') }}" class="dash-input w-full" placeholder="+1 555 0100">
            </div>

            <div class="dash-field">
                <label class="dash-label" for="access_token">Permanent access token</label>
                <input type="password" id="access_token" name="access_token" class="dash-input w-full" placeholder="{{ $channel ? 'Leave blank to keep current token' : 'EAA…' }}">
                @error('access_token')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="dash-field">
                <label class="dash-label" for="website_id">Route chats to website</label>
                <select id="website_id" name="website_id" class="dash-select w-full">
                    <option value="">First active website in org</option>
                    @foreach($websites as $website)
                        <option value="{{ $website->id }}" @selected(old('website_id', $channel->website_id ?? '') == $website->id)>{{ $website->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs dash-muted mt-1">Knowledge base, AI settings, and automation rules use this website.</p>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $channel->is_active ?? true))>
                Channel active
            </label>

            <div class="flex justify-end">
                <button type="submit" class="dash-btn-primary">Save WhatsApp channel</button>
            </div>
        </div>
    </form>
</div>
@endsection
