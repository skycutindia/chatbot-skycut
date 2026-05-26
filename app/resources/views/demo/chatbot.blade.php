@extends('demo.layout', ['currentPage' => 'chatbot'])

@php
    $c = $website->configuration;
    $modules = $c->modules();
@endphp

@section('title', 'AI Chatbot')

@section('content')
<section class="demo-section" style="padding-top:3rem;">
    <div class="demo-section-inner">
        <div class="demo-section-head">
            <h2>AI Chatbot Configuration</h2>
            <p>Live settings from your dashboard. Edit in <a href="{{ route('login') }}" style="color:var(--demo-brand);">admin</a> → Websites.</p>
        </div>

        <div class="demo-config-layout">
            <div>
                <div class="demo-config-panel">
                    <h3>Appearance & messaging</h3>
                    <div class="demo-config-row"><span class="demo-config-label">Bot name</span><span class="demo-config-value">{{ $c->bot_name ?: 'Assistant' }}</span></div>
                    <div class="demo-config-row"><span class="demo-config-label">Theme</span><span class="demo-config-value">{{ ucfirst($c->theme_mode ?? 'light') }}</span></div>
                    <div class="demo-config-row"><span class="demo-config-label">Position</span><span class="demo-config-value">{{ ucfirst($c->position ?? 'right') }}</span></div>
                    <div class="demo-config-row"><span class="demo-config-label">Primary color</span><span class="demo-config-value">{{ $c->primary_color ?? '#0d9488' }}</span></div>
                    <div class="demo-config-row"><span class="demo-config-label">Welcome message</span><span class="demo-config-value">{{ Str::limit($c->welcome_message, 80) ?: '—' }}</span></div>
                </div>

                <div class="demo-config-panel" style="margin-top:1rem;">
                    <h3>Modules</h3>
                    @foreach($modules as $key => $enabled)
                        <div class="demo-config-row">
                            <span class="demo-config-label">{{ str_replace('_', ' ', ucfirst($key)) }}</span>
                            <span class="demo-config-value">{{ $enabled ? 'Enabled' : 'Off' }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="demo-config-panel" style="margin-top:1rem;">
                    <h3>Visitor session rules</h3>
                    <div class="demo-config-row"><span class="demo-config-label">Page refresh</span><span class="demo-config-value">New chat (client cleared)</span></div>
                    <div class="demo-config-row"><span class="demo-config-label">Close widget</span><span class="demo-config-value">Archived in dashboard</span></div>
                    <div class="demo-config-row"><span class="demo-config-label">Live agent notice</span><span class="demo-config-value">Shown once per session</span></div>
                </div>

                <p class="demo-note">
                    Embed: <code style="font-size:0.75rem;word-break:break-all;">{{ $website->simpleEmbedSnippet() }}</code>
                </p>
            </div>

            <div class="demo-chat-frame">
                @include('demo.partials.chat-preview')
            </div>
        </div>
    </div>
</section>
@endsection
