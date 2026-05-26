@extends('layouts.websites-workspace')

@section('title', 'Bot — '.$website->name)

@section('ws-tab', 'bot')
@section('ws-step', '2')
@section('ws-step-label', 'Bot')
@section('ws-title', 'Bot information')
@section('ws-subtitle', 'How your chatbot looks and speaks to visitors on the widget.')

@section('workspace')
@php $c = $website->configuration; @endphp
    <form method="POST" action="{{ route('websites.update.bot', $website) }}" class="ws-settings-form" id="bot-settings-form">
        @csrf @method('PUT')

        <div class="ws-settings-layout">
            <div class="ws-settings-main">
                <section class="ws-settings-card">
                    <header class="ws-settings-card__head">
                        <span class="ws-settings-card__icon" aria-hidden="true">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3z"/><path stroke-linecap="round" d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
                        </span>
                        <div>
                            <h3 class="ws-settings-card__title">Identity</h3>
                            <p class="ws-settings-card__desc">Name and avatar shown in the chat header.</p>
                        </div>
                    </header>
                    <div class="ws-settings-card__body">
                        <div class="ws-field">
                            <label class="ws-label" for="bot_name">Bot name <span class="ws-required">*</span></label>
                            <input type="text" id="bot_name" name="bot_name" value="{{ old('bot_name', $c->bot_name) }}" required class="ws-input" data-preview="name">
                        </div>
                        <div class="ws-field">
                            <label class="ws-label" for="avatar_url">Avatar image URL</label>
                            <input type="url" id="avatar_url" name="avatar_url" value="{{ old('avatar_url', $c->avatar_url) }}" class="ws-input" placeholder="https://…" data-preview="avatar">
                        </div>
                        <div class="ws-field">
                            <label class="ws-label" for="bot_description">Short description</label>
                            <input type="text" id="bot_description" name="bot_description" value="{{ old('bot_description', $c->bot_description) }}" class="ws-input" placeholder="Optional subtitle for agents">
                        </div>
                    </div>
                </section>

                <section class="ws-settings-card">
                    <header class="ws-settings-card__head">
                        <span class="ws-settings-card__icon" aria-hidden="true">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" d="M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </span>
                        <div>
                            <h3 class="ws-settings-card__title">Messages</h3>
                            <p class="ws-settings-card__desc">First message and fallback when AI is unsure.</p>
                        </div>
                    </header>
                    <div class="ws-settings-card__body">
                        <div class="ws-field">
                            <label class="ws-label" for="welcome_message">Welcome message</label>
                            <textarea id="welcome_message" name="welcome_message" rows="3" class="ws-textarea" data-preview="welcome">{{ old('welcome_message', $c->welcome_message) }}</textarea>
                        </div>
                        <div class="ws-field">
                            <label class="ws-label" for="fallback_message">Fallback message</label>
                            <textarea id="fallback_message" name="fallback_message" rows="2" class="ws-textarea" placeholder="When the bot cannot answer confidently…">{{ old('fallback_message', $c->fallback_message) }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="ws-settings-card">
                    <header class="ws-settings-card__head">
                        <span class="ws-settings-card__icon" aria-hidden="true">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </span>
                        <div>
                            <h3 class="ws-settings-card__title">Personality</h3>
                            <p class="ws-settings-card__desc">Tone and language for AI replies.</p>
                        </div>
                    </header>
                    <div class="ws-settings-card__body ws-settings-card__body--grid">
                        <div class="ws-field">
                            <label class="ws-label" for="ai_tone">AI tone</label>
                            <select id="ai_tone" name="ai_tone" class="ws-select">
                                @foreach(['professional' => 'Professional', 'friendly' => 'Friendly', 'casual' => 'Casual', 'formal' => 'Formal'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('ai_tone', $c->ai_tone) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ws-field">
                            <label class="ws-label" for="locale">Default language</label>
                            <input type="text" id="locale" name="locale" value="{{ old('locale', $c->locale ?? 'en') }}" class="ws-input" placeholder="en">
                        </div>
                    </div>
                </section>

                <section class="ws-settings-card">
                    <header class="ws-settings-card__head">
                        <span class="ws-settings-card__icon" aria-hidden="true">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a6 6 0 00-6-6H7"/></svg>
                        </span>
                        <div>
                            <h3 class="ws-settings-card__title">Appearance</h3>
                            <p class="ws-settings-card__desc">Brand colors for the chat widget.</p>
                        </div>
                    </header>
                    <div class="ws-settings-card__body">
                        <div class="ws-color-row">
                            <div class="ws-field ws-field--color">
                                <label class="ws-label" for="primary_color">Primary</label>
                                <div class="ws-color-input">
                                    <input type="color" id="primary_color" name="primary_color" value="{{ old('primary_color', $c->primary_color ?? '#0d9488') }}" class="ws-color-picker" data-preview="primary">
                                    <input type="text" class="ws-input ws-color-hex" value="{{ old('primary_color', $c->primary_color ?? '#0d9488') }}" data-sync-color="primary_color" readonly tabindex="-1" aria-hidden="true">
                                </div>
                            </div>
                            <div class="ws-field ws-field--color">
                                <label class="ws-label" for="secondary_color">Secondary</label>
                                <div class="ws-color-input">
                                    <input type="color" id="secondary_color" name="secondary_color" value="{{ old('secondary_color', $c->secondary_color ?? '#14b8a6') }}" class="ws-color-picker" data-preview="secondary">
                                    <input type="text" class="ws-input ws-color-hex" value="{{ old('secondary_color', $c->secondary_color ?? '#14b8a6') }}" data-sync-color="secondary_color" readonly tabindex="-1" aria-hidden="true">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="ws-settings-card">
                    <header class="ws-settings-card__head">
                        <span class="ws-settings-card__icon" aria-hidden="true">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        </span>
                        <div>
                            <h3 class="ws-settings-card__title">Behavior</h3>
                            <p class="ws-settings-card__desc">Widget experience toggles.</p>
                        </div>
                    </header>
                    <div class="ws-settings-card__body ws-settings-toggles">
                        <label class="ws-toggle">
                            <input type="checkbox" name="typing_animation" value="1" @checked(old('typing_animation', $c->typing_animation ?? true)) data-preview="typing">
                            <span class="ws-toggle__track" aria-hidden="true"></span>
                            <span class="ws-toggle__text">
                                <strong>Typing animation</strong>
                                <small>Show “typing…” while the bot responds</small>
                            </span>
                        </label>
                        <label class="ws-toggle">
                            <input type="checkbox" name="bot_online" value="1" @checked(old('bot_online', $c->bot_online ?? true)) data-preview="online">
                            <span class="ws-toggle__track" aria-hidden="true"></span>
                            <span class="ws-toggle__text">
                                <strong>Show as online</strong>
                                <small>Green status indicator in the widget</small>
                            </span>
                        </label>
                        <label class="ws-toggle">
                            <input type="checkbox" name="ai_enabled" value="1" @checked(old('ai_enabled', $c->ai_enabled ?? true))>
                            <span class="ws-toggle__track" aria-hidden="true"></span>
                            <span class="ws-toggle__text">
                                <strong>AI responses</strong>
                                <small>Use AI when no Q&amp;A match is found</small>
                            </span>
                        </label>
                    </div>
                </section>
            </div>

            <div class="ws-settings-aside">
                @include('dashboard.websites.partials.bot-preview', ['website' => $website])
            </div>
        </div>

        <div class="ws-settings-savebar">
            <a href="{{ route('websites.edit', $website) }}" class="ws-btn-ghost">← Website info</a>
            <div class="ws-settings-savebar__actions">
                <a href="{{ route('websites.embed', $website) }}" class="ws-btn-ghost">Plugin &amp; embed</a>
                <button type="submit" class="ws-btn-primary">Save bot settings</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    const widget = document.getElementById('bot-preview-widget');
    if (!widget) return;

    const nameEl = document.getElementById('bot-preview-name');
    const welcomeEl = document.getElementById('bot-preview-welcome');
    const statusEl = document.getElementById('bot-preview-status');
    const typingEl = document.getElementById('bot-preview-typing');
    const avatarImg = document.getElementById('bot-preview-avatar-img');
    const avatarFallback = document.getElementById('bot-preview-avatar-fallback');

    function setColors() {
        const primary = document.getElementById('primary_color')?.value || '#0d9488';
        const secondary = document.getElementById('secondary_color')?.value || '#14b8a6';
        widget.style.setProperty('--preview-primary', primary);
        widget.style.setProperty('--preview-secondary', secondary);
        document.querySelectorAll('[data-sync-color="primary_color"]').forEach((el) => { el.value = primary; });
        document.querySelectorAll('[data-sync-color="secondary_color"]').forEach((el) => { el.value = secondary; });
    }

    document.getElementById('bot_name')?.addEventListener('input', (e) => {
        if (nameEl) nameEl.textContent = e.target.value || 'Assistant';
    });

    document.getElementById('welcome_message')?.addEventListener('input', (e) => {
        if (welcomeEl) welcomeEl.textContent = e.target.value || 'Hi! How can we help you today?';
    });

    document.querySelector('[data-preview="online"]')?.addEventListener('change', (e) => {
        if (statusEl) {
            statusEl.textContent = e.target.checked ? 'Online' : 'Offline';
            statusEl.classList.toggle('is-offline', !e.target.checked);
        }
    });

    document.querySelector('[data-preview="typing"]')?.addEventListener('change', (e) => {
        typingEl?.toggleAttribute('hidden', !e.target.checked);
    });

    document.getElementById('avatar_url')?.addEventListener('input', (e) => {
        const url = e.target.value.trim();
        if (url && avatarImg) {
            avatarImg.src = url;
            avatarImg.hidden = false;
            avatarFallback?.setAttribute('hidden', '');
        } else {
            avatarImg && (avatarImg.hidden = true);
            avatarFallback?.removeAttribute('hidden');
        }
    });

    document.getElementById('primary_color')?.addEventListener('input', setColors);
    document.getElementById('secondary_color')?.addEventListener('input', setColors);
    setColors();
})();
</script>
@endpush
