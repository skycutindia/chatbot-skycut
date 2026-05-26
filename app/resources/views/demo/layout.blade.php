@php
    $brand = $website->configuration->primary_color ?? '#0d9488';
    $brandLight = $website->configuration->secondary_color ?? '#14b8a6';
    $botName = $website->configuration->bot_name ?? 'Assistant';
    $currentPage = $currentPage ?? 'home';
    $demoBase = route('demo.show', $website->demo_slug);
@endphp
<!DOCTYPE html>
<html lang="en" class="demo-site">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $website->name) — {{ $website->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/framework.css') }}">
    <link rel="stylesheet" href="{{ asset('css/demo-site.css') }}">
    <style>:root { --demo-brand: {{ $brand }}; --demo-brand-light: {{ $brandLight }}; }</style>
    @stack('head')
</head>
<body style="--demo-brand: {{ $brand }}">

<header class="demo-header">
    <div class="demo-header-inner">
        <a href="{{ $demoBase }}" class="demo-logo">
            <span class="demo-logo-mark" style="background: linear-gradient(135deg, {{ $brand }}, {{ $brandLight }});">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="3"/></svg>
            </span>
            {{ $website->name }}
        </a>
        <nav class="demo-nav" aria-label="Main">
            <a href="{{ $demoBase }}" class="{{ $currentPage === 'home' ? 'is-active' : '' }}">Home</a>
            <a href="{{ route('demo.page', [$website->demo_slug, 'features']) }}" class="{{ $currentPage === 'features' ? 'is-active' : '' }}">Features</a>
            <a href="{{ route('demo.page', [$website->demo_slug, 'pricing']) }}" class="{{ $currentPage === 'pricing' ? 'is-active' : '' }}">Pricing</a>
            <a href="{{ route('demo.page', [$website->demo_slug, 'chatbot']) }}" class="{{ $currentPage === 'chatbot' ? 'is-active' : '' }}">AI Chatbot</a>
            <a href="{{ route('demo.page', [$website->demo_slug, 'contact']) }}" class="{{ $currentPage === 'contact' ? 'is-active' : '' }}">Contact</a>
        </nav>
        <div class="demo-nav-actions">
            <a href="{{ route('login') }}" class="demo-btn demo-btn-ghost">Admin</a>
            <a href="{{ route('demo.page', [$website->demo_slug, 'chatbot']) }}" class="demo-btn demo-btn-primary">Try chatbot</a>
            <button type="button" class="demo-mobile-toggle" id="demo-menu-toggle" aria-label="Menu" aria-expanded="false">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
    <nav class="demo-mobile-nav" id="demo-mobile-nav" aria-label="Mobile">
        <a href="{{ $demoBase }}">Home</a>
        <a href="{{ route('demo.page', [$website->demo_slug, 'features']) }}">Features</a>
        <a href="{{ route('demo.page', [$website->demo_slug, 'pricing']) }}">Pricing</a>
        <a href="{{ route('demo.page', [$website->demo_slug, 'chatbot']) }}">AI Chatbot</a>
        <a href="{{ route('demo.page', [$website->demo_slug, 'contact']) }}">Contact</a>
        <a href="{{ route('login') }}">Admin dashboard</a>
    </nav>
</header>

<main>
    @yield('content')
</main>

<footer class="demo-footer">
    <div class="demo-footer-inner">
        <div>
            <div class="demo-logo" style="color:#fff;margin-bottom:1rem;">
                <span class="demo-logo-mark" style="background:{{ $brand }};">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="3"/></svg>
                </span>
                {{ $website->name }}
            </div>
            <p style="font-size:0.875rem;line-height:1.6;max-width:18rem;">AI-powered customer support. Each page refresh starts a fresh visitor chat session.</p>
        </div>
        <div>
            <h4 style="color:#fff;font-weight:600;margin-bottom:1rem;">Product</h4>
            <ul style="list-style:none;padding:0;margin:0;font-size:0.875rem;display:flex;flex-direction:column;gap:0.5rem;">
                <li><a href="{{ route('demo.page', [$website->demo_slug, 'features']) }}" style="color:inherit;text-decoration:none;">Features</a></li>
                <li><a href="{{ route('demo.page', [$website->demo_slug, 'pricing']) }}" style="color:inherit;text-decoration:none;">Pricing</a></li>
                <li><a href="{{ route('demo.page', [$website->demo_slug, 'chatbot']) }}" style="color:inherit;text-decoration:none;">Chatbot config</a></li>
            </ul>
        </div>
        <div>
            <h4 style="color:#fff;font-weight:600;margin-bottom:1rem;">Support</h4>
            <ul style="list-style:none;padding:0;margin:0;font-size:0.875rem;display:flex;flex-direction:column;gap:0.5rem;">
                <li><a href="{{ route('demo.page', [$website->demo_slug, 'contact']) }}" style="color:inherit;text-decoration:none;">Contact</a></li>
                <li><a href="{{ route('login') }}" style="color:inherit;text-decoration:none;">Agent login</a></li>
            </ul>
        </div>
        <div>
            <h4 style="color:#fff;font-weight:600;margin-bottom:1rem;">Chat behavior</h4>
            <p style="font-size:0.8125rem;line-height:1.55;">Closing the widget archives the chat in your dashboard. Refreshing this site clears the visitor&apos;s local chat only.</p>
        </div>
    </div>
    <div class="demo-footer-bottom">© {{ date('Y') }} {{ $website->name }} · Demo · Powered by AI Chatbot Hub Pro</div>
</footer>

<script src="{{ rtrim(config('app.url'), '/') }}/widget/chatbot.js"
        data-bot-token="{{ $website->bot_token }}"
        async></script>

<script>
document.querySelectorAll('[data-demo-open-chat]').forEach((btn) => {
    btn.addEventListener('click', () => {
        if (window.ChatFlow?.open) {
            window.ChatFlow.open();
        } else {
            document.getElementById('chatflow-launcher')?.click();
        }
    });
});
document.getElementById('demo-menu-toggle')?.addEventListener('click', function () {
    const nav = document.getElementById('demo-mobile-nav');
    const open = nav?.classList.toggle('is-open');
    this.setAttribute('aria-expanded', open ? 'true' : 'false');
});
</script>
@stack('scripts')
</body>
</html>
