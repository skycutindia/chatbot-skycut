@extends('layouts.websites-wizard')

@section('title', 'New Chatbot — Step 2')

@section('ws-wizard-step', '2')
@section('ws-title', 'Bot information')
@section('ws-subtitle')
For {{ $wizard['name'] }} — widget appearance and AI behavior.
@endsection

@section('workspace')
    <form method="POST" action="{{ route('websites.store') }}" class="ws-wizard-card">
        <div class="ws-wizard-card__body">
            @csrf
            <div class="ws-field">
                <label class="ws-label" for="bot_name">Bot name <span class="ws-required">*</span></label>
                <input type="text" id="bot_name" name="bot_name" value="{{ old('bot_name', $wizard['name'].' Assistant') }}" required class="ws-input">
            </div>
            <div class="ws-field">
                <label class="ws-label" for="welcome_message">Welcome message</label>
                <textarea id="welcome_message" name="welcome_message" rows="3" class="ws-textarea">{{ old('welcome_message', 'Hi! How can we help you today?') }}</textarea>
            </div>
            <div class="ws-field">
                <label class="ws-label" for="avatar_url">Bot avatar URL</label>
                <input type="url" id="avatar_url" name="avatar_url" value="{{ old('avatar_url') }}" class="ws-input">
            </div>
            <div class="ws-field">
                <label class="ws-label" for="bot_description">Bot description</label>
                <textarea id="bot_description" name="bot_description" rows="2" class="ws-textarea">{{ old('bot_description') }}</textarea>
            </div>
            <div class="ws-field-row">
                <div class="ws-field">
                    <label class="ws-label" for="ai_tone">AI tone / personality</label>
                    <select id="ai_tone" name="ai_tone" class="ws-select">
                        @foreach(['professional', 'friendly', 'casual', 'formal'] as $tone)
                            <option value="{{ $tone }}" @selected(old('ai_tone', 'professional') === $tone)>{{ ucfirst($tone) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ws-field">
                    <label class="ws-label" for="locale">Default language</label>
                    <input type="text" id="locale" name="locale" value="{{ old('locale', $wizard['language'] ?? 'en') }}" class="ws-input">
                </div>
            </div>
            <div class="ws-field">
                <label class="ws-label" for="fallback_message">Fallback message</label>
                <textarea id="fallback_message" name="fallback_message" rows="2" class="ws-textarea" placeholder="I'm not sure about that. Would you like to speak with our team?">{{ old('fallback_message') }}</textarea>
            </div>
            <div class="ws-color-row">
                <div class="ws-field">
                    <label class="ws-label" for="primary_color">Primary color</label>
                    <input type="color" id="primary_color" name="primary_color" value="{{ old('primary_color', '#0d9488') }}" class="ws-color-picker">
                </div>
                <div class="ws-field">
                    <label class="ws-label" for="secondary_color">Secondary color</label>
                    <input type="color" id="secondary_color" name="secondary_color" value="{{ old('secondary_color', '#14b8a6') }}" class="ws-color-picker">
                </div>
            </div>
            <div class="ws-settings-toggles">
                <label class="ws-toggle">
                    <input type="checkbox" name="typing_animation" value="1" checked>
                    <span class="ws-toggle__track" aria-hidden="true"></span>
                    <span class="ws-toggle__text"><strong>Typing animation</strong></span>
                </label>
                <label class="ws-toggle">
                    <input type="checkbox" name="bot_online" value="1" checked>
                    <span class="ws-toggle__track" aria-hidden="true"></span>
                    <span class="ws-toggle__text"><strong>Show bot as online</strong></span>
                </label>
                <label class="ws-toggle">
                    <input type="checkbox" name="ai_enabled" value="1" checked>
                    <span class="ws-toggle__track" aria-hidden="true"></span>
                    <span class="ws-toggle__text"><strong>Enable AI responses</strong></span>
                </label>
            </div>
        </div>
        <footer class="ws-wizard-card__foot">
            <a href="{{ route('websites.create') }}" class="ws-btn-ghost">← Back</a>
            <button type="submit" class="ws-btn-primary">Create &amp; generate plugin</button>
        </footer>
    </form>
@endsection
