<!DOCTYPE html>
<html lang="en" class="dash-layout">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="session-expired-url" content="{{ route('auth.session-expired') }}">
    <meta name="logout-beacon-url" content="{{ route('auth.logout-beacon') }}">
    <title>@yield('title', 'Dashboard') — SkyCut</title>
    <script>
        (function () {
            var stored = localStorage.getItem('dashboardTheme') || localStorage.getItem('dashboard-theme') || 'system';
            var effective = stored;
            if (stored === 'system' || stored === '' || stored === null) {
                effective = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', effective);
            document.documentElement.dataset.themeChoice = stored || 'system';
        })();
    </script>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('css/framework.css') }}?v={{ filemtime(public_path('css/framework.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-theme.css') }}?v={{ filemtime(public_path('css/dashboard-theme.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/website-workspace.css') }}?v={{ filemtime(public_path('css/website-workspace.css')) }}">
    @auth
        @if(auth()->user()->roleEnum()->canHandleLiveChat())
            <link rel="manifest" href="{{ route('agent.pwa.manifest') }}">
            <meta name="theme-color" content="{{ config('chatbot.pwa.theme_color') }}">
            <meta name="apple-mobile-web-app-capable" content="yes">
            <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
            <meta name="agent-poll-url" content="{{ route('inbox.poll') }}">
            <meta name="agent-push-subscribe-url" content="{{ route('inbox.push.subscribe') }}">
            <meta name="vapid-public-key" content="{{ config('chatbot.pwa.vapid_public_key') }}">
        @endif
    @endauth
    @stack('styles')
    @auth
        @if(auth()->user()->organization_id)
            @php
                $realtimePage = request()->routeIs('inbox.*') ? 'inbox' : (request()->routeIs('websites.conversations.show') ? 'conversation' : (request()->routeIs('websites.hub') ? 'hub' : null));
                $realtimeConversationId = request()->route('conversation')?->id;
                $realtimeMessagesUrl = request()->routeIs('websites.conversations.show')
                    ? route('websites.conversations.messages', [request()->route('website'), request()->route('conversation')])
                    : null;
            @endphp
            <script>
                window.__CHATBOT_REALTIME__ = {
                    orgId: {{ auth()->user()->organization_id }},
                    page: @json($realtimePage),
                    conversationId: @json($realtimeConversationId),
                    messagesUrl: @json($realtimeMessagesUrl),
                };
            </script>
            @if (file_exists(public_path('build/manifest.json')))
                @vite(['resources/js/realtime.js'])
            @endif
        @endif
    @endauth
</head>
<body class="dash-body">
<div class="dash-shell">
    <div id="dash-sidebar-backdrop" class="dash-sidebar-backdrop" aria-hidden="true"></div>
    @include('layouts.partials.sidebar')

    <div class="dash-shell-main">
        @include('layouts.partials.topbar')

        @hasSection('page-toolbar')
            <div class="dash-page-toolbar">
                <div class="dash-page-toolbar-inner">
                    @yield('page-toolbar')
                </div>
            </div>
        @endif

        <main class="dash-shell-content dash-main" id="dash-main-scroll">
            @if(session('success'))
                <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
            @endif
            @auth
                @if(auth()->user()->roleEnum()->canHandleLiveChat())
                    <div id="agent-pwa-install" class="agent-pwa-bar" hidden>
                        <div class="agent-pwa-bar-inner">
                            <span class="agent-pwa-bar-text">Install the Live Agent app for quick mobile access.</span>
                            <div class="agent-pwa-bar-actions">
                                <button type="button" id="agent-pwa-install-btn" class="dash-btn-primary dash-btn-sm">Install app</button>
                                <button type="button" id="agent-enable-notify" class="dash-btn-secondary dash-btn-sm">Enable notifications</button>
                                <button type="button" id="agent-pwa-install-dismiss" class="dash-btn-ghost dash-btn-sm" aria-label="Dismiss">Dismiss</button>
                            </div>
                        </div>
                    </div>
                @endif
            @endauth
            @yield('content')
        </main>
    </div>
</div>

<div id="ws-action-sheet-root" class="ws-action-sheet-backdrop" aria-hidden="true">
    <div class="ws-action-sheet" role="dialog" aria-modal="true" aria-labelledby="ws-action-sheet-title">
        <div class="ws-action-sheet-handle" aria-hidden="true"></div>
        <div class="ws-action-sheet-header">
            <h3 id="ws-action-sheet-title">Website actions</h3>
            <button type="button" class="ws-action-sheet-close" id="ws-action-sheet-close" aria-label="Close">×</button>
        </div>
        <div class="ws-action-sheet-body" id="ws-action-sheet-body"></div>
    </div>
</div>

<script src="{{ asset('js/dashboard-ui.js') }}?v={{ filemtime(public_path('js/dashboard-ui.js')) }}"></script>
@auth
    @if(auth()->user()->roleEnum()->canHandleLiveChat())
        <script src="{{ asset('js/agent-pwa.js') }}?v={{ filemtime(public_path('js/agent-pwa.js')) }}"></script>
        <script src="{{ asset('js/mention-feed.js') }}?v={{ filemtime(public_path('js/mention-feed.js')) }}"></script>
    @endif
@endauth
@stack('scripts')
<script>
    (function () {
        const toggle = document.getElementById('mobile-nav-toggle');
        const sidebar = document.getElementById('dash-sidebar');
        const backdrop = document.getElementById('dash-sidebar-backdrop');

        function closeSidebar() {
            sidebar?.classList.remove('dash-sidebar-open');
            backdrop?.classList.remove('dash-sidebar-backdrop-visible');
        }

        toggle?.addEventListener('click', () => {
            const open = sidebar?.classList.toggle('dash-sidebar-open');
            backdrop?.classList.toggle('dash-sidebar-backdrop-visible', open);
        });

        backdrop?.addEventListener('click', closeSidebar);
    })();
</script>
</body>
</html>
