@extends('layouts.app')

@section('title', 'Platform Settings')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Platform admin</p>
        <h1 class="dash-page-title">Platform Settings</h1>
        <p class="dash-page-sub">Global AI and system configuration</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow">
    <a href="{{ route('dashboard') }}" class="dash-back-link">← Dashboard</a>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="dash-card mt-8">
        <div class="dash-card-body space-y-4">
            @csrf @method('PUT')
            <div class="dash-field">
                <label class="dash-label" for="platform_name">Platform name</label>
                <input id="platform_name" name="platform_name" value="{{ old('platform_name', $platformName) }}" class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="openai_api_key">OpenAI API key</label>
                <input id="openai_api_key" type="password" name="openai_api_key" placeholder="Leave blank to keep current" class="dash-input w-full">
                <p class="text-xs dash-muted mt-1">Overrides .env when set. Used as fallback for all tenants.</p>
            </div>
            <div class="dash-field">
                <label class="dash-label" for="openai_default_model">Default OpenAI model</label>
                <input id="openai_default_model" name="openai_default_model" value="{{ old('openai_default_model', $openaiModel) }}" class="dash-input w-full">
            </div>
            <label class="dash-checkbox-row">
                <input type="checkbox" name="maintenance_mode" value="1" @checked($maintenanceMode)>
                Maintenance mode
            </label>
            <button type="submit" class="dash-btn-primary">Save settings</button>
        </div>
    </form>
</div>
@endsection
