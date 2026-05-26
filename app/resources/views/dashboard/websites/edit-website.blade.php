@extends('layouts.websites-workspace')

@section('title', 'Website — '.$website->name)

@section('ws-tab', 'settings')
@section('ws-step', '1')
@section('ws-step-label', 'Website')
@section('ws-title', 'Website information')
@section('ws-subtitle', 'Site identity, contact details, and live status.')

@section('workspace')
    <form method="POST" action="{{ route('websites.update.website', $website) }}" class="ws-settings-form">
        @csrf @method('PUT')

        <div class="ws-settings-layout ws-settings-layout--single">
            <div class="ws-settings-main">
                <section class="ws-settings-card">
                    <header class="ws-settings-card__head">
                        <span class="ws-settings-card__icon" aria-hidden="true">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                        </span>
                        <div>
                            <h3 class="ws-settings-card__title">General</h3>
                            <p class="ws-settings-card__desc">How this site appears in your dashboard.</p>
                        </div>
                    </header>
                    <div class="ws-settings-card__body">
                        <div class="ws-field">
                            <label class="ws-label" for="name">Website name <span class="ws-required">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name', $website->name) }}" required class="ws-input">
                        </div>
                        <div class="ws-field">
                            <label class="ws-label" for="url">Website URL</label>
                            <input type="url" id="url" name="url" value="{{ old('url', $website->url) }}" class="ws-input" placeholder="https://example.com">
                        </div>
                        <div class="ws-field-row">
                            <div class="ws-field">
                                <label class="ws-label" for="category">Category</label>
                                <input type="text" id="category" name="category" value="{{ old('category', $website->category) }}" class="ws-input" placeholder="E-commerce, SaaS…">
                            </div>
                            <div class="ws-field">
                                <label class="ws-label" for="language">Language</label>
                                <input type="text" id="language" name="language" value="{{ old('language', $website->language ?? 'en') }}" class="ws-input">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="ws-settings-card">
                    <header class="ws-settings-card__head">
                        <span class="ws-settings-card__icon" aria-hidden="true">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </span>
                        <div>
                            <h3 class="ws-settings-card__title">Contact &amp; branding</h3>
                            <p class="ws-settings-card__desc">Optional logo and support email.</p>
                        </div>
                    </header>
                    <div class="ws-settings-card__body">
                        <div class="ws-field">
                            <label class="ws-label" for="contact_email">Contact email</label>
                            <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $website->contact_email) }}" class="ws-input">
                        </div>
                        <div class="ws-field">
                            <label class="ws-label" for="logo_url">Logo URL</label>
                            <input type="url" id="logo_url" name="logo_url" value="{{ old('logo_url', $website->logo_url) }}" class="ws-input" placeholder="https://…">
                        </div>
                    </div>
                </section>

                <section class="ws-settings-card">
                    <header class="ws-settings-card__head">
                        <span class="ws-settings-card__icon" aria-hidden="true">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </span>
                        <div>
                            <h3 class="ws-settings-card__title">Status</h3>
                            <p class="ws-settings-card__desc">Pause to hide the widget from visitors.</p>
                        </div>
                    </header>
                    <div class="ws-settings-card__body ws-settings-toggles">
                        <label class="ws-toggle">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $website->is_active))>
                            <span class="ws-toggle__track" aria-hidden="true"></span>
                            <span class="ws-toggle__text">
                                <strong>Website active</strong>
                                <small>Chatbot is live on embedded pages</small>
                            </span>
                        </label>
                    </div>
                </section>
            </div>
        </div>

        <div class="ws-settings-savebar">
            <a href="{{ route('websites.index') }}" class="ws-btn-ghost">← All websites</a>
            <div class="ws-settings-savebar__actions">
                <a href="{{ route('websites.edit.bot', $website) }}" class="ws-btn-ghost">Bot settings →</a>
                <button type="submit" class="ws-btn-primary">Save website</button>
            </div>
        </div>
    </form>
@endsection
