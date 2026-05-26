@extends('layouts.app')

@section('title', 'New Website')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Websites</p>
        <h1 class="dash-page-title">Create website</h1>
        <p class="dash-page-sub">A unique bot token and embed snippet will be generated automatically.</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-narrow">

    <form method="POST" action="{{ route('websites.store') }}" class="dash-card mt-8">
        <div class="dash-card-body space-y-4">
            @csrf
            <div class="dash-field">
                <label class="dash-label" for="name">Website name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="url">Website URL</label>
                <input type="url" id="url" name="url" value="{{ old('url') }}" placeholder="https://example.com" class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="domain">Allowed domain (optional)</label>
                <input type="text" id="domain" name="domain" value="{{ old('domain') }}" placeholder="example.com" class="dash-input w-full">
            </div>
            <button type="submit" class="dash-btn-primary w-full">Create chatbot</button>
        </div>
    </form>
</div>
@endsection
