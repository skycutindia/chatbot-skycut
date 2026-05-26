@extends('layouts.websites-wizard')

@section('title', 'New Website — Step 1')

@section('ws-wizard-step', '1')
@section('ws-title', 'Website information')
@section('ws-subtitle', 'Each website gets its own bot, training data, and plugin package.')

@section('workspace')
    <form method="POST" action="{{ route('websites.store.step1') }}" class="ws-wizard-card">
        <div class="ws-wizard-card__body">
            @csrf
            <div class="ws-field">
                <label class="ws-label" for="name">Website name <span class="ws-required">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="ws-input">
            </div>
            <div class="ws-field">
                <label class="ws-label" for="url">Website URL</label>
                <input type="url" id="url" name="url" value="{{ old('url') }}" placeholder="https://example.com" class="ws-input">
            </div>
            <div class="ws-field">
                <label class="ws-label" for="logo_url">Website logo URL</label>
                <input type="url" id="logo_url" name="logo_url" value="{{ old('logo_url') }}" class="ws-input">
            </div>
            <div class="ws-field-row">
                <div class="ws-field">
                    <label class="ws-label" for="category">Website category</label>
                    <input type="text" id="category" name="category" value="{{ old('category') }}" placeholder="E-commerce, SaaS…" class="ws-input">
                </div>
                <div class="ws-field">
                    <label class="ws-label" for="language">Website language</label>
                    <input type="text" id="language" name="language" value="{{ old('language', 'en') }}" class="ws-input">
                </div>
            </div>
            <div class="ws-field">
                <label class="ws-label" for="contact_email">Contact email</label>
                <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email') }}" class="ws-input">
            </div>
            <div class="ws-field">
                <label class="ws-label" for="domain">Allowed domain (optional)</label>
                <input type="text" id="domain" name="domain" value="{{ old('domain') }}" placeholder="example.com" class="ws-input">
            </div>
            <label class="ws-toggle">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                <span class="ws-toggle__track" aria-hidden="true"></span>
                <span class="ws-toggle__text">
                    <strong>Website active</strong>
                    <small>Bot will be live after setup</small>
                </span>
            </label>
        </div>
        <footer class="ws-wizard-card__foot">
            <a href="{{ route('websites.index') }}" class="ws-btn-ghost">Cancel</a>
            <button type="submit" class="ws-btn-primary">Continue to bot setup →</button>
        </footer>
    </form>
@endsection
