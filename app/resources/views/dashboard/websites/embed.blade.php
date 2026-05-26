@extends('layouts.websites-workspace')

@section('title', 'Plugin — '.$website->name)

@section('ws-tab', 'embed')
@section('ws-title', 'Installation center')
@section('ws-subtitle', 'Auto-generated plugin package and embed code for '.$website->name.'.')

@section('page-toolbar')
<div class="cf-cluster ws-toolbar-actions">
    <a href="{{ route('websites.embed.download', $website) }}" class="dash-btn-primary dash-btn-sm">Download plugin ZIP</a>
    <form method="POST" action="{{ route('websites.embed.regenerate', $website) }}" class="inline">
        @csrf
        <button type="submit" class="dash-btn-secondary dash-btn-sm">Regenerate plugin</button>
    </form>
</div>
@endsection

@section('workspace')
    <div class="ws-stack">
        <section class="ws-panel">
            <header class="ws-panel__head"><h2 class="ws-panel__title">Plugin package</h2></header>
            <div class="ws-panel__body">
                <p class="ws-panel__lead">Generated on create and when you regenerate. ZIP includes <code>widget.js</code>, <code>widget.css</code>, <code>config.json</code>, <code>install-guide.html</code>, and <code>README.txt</code>.</p>
                <div class="ws-btn-row">
                    <a href="{{ route('websites.embed.download', $website) }}" class="ws-btn-primary ws-btn-sm">Download plugin ZIP</a>
                    @if($hasInstallGuide ?? false)
                        <a href="{{ route('websites.embed.install-guide', $website) }}" target="_blank" rel="noopener" class="ws-btn-secondary ws-btn-sm">View installation guide</a>
                    @endif
                    <a href="{{ route('websites.embed.readme', $website) }}" class="ws-btn-secondary ws-btn-sm">Download README.txt</a>
                </div>
            </div>
        </section>

        <section class="ws-panel">
            <header class="ws-panel__head">
                <h2 class="ws-panel__title">Embed code</h2>
                <button type="button" class="ws-btn-ghost ws-btn-sm" data-copy-target="embed-snippet">Copy embed code</button>
            </header>
            <div class="ws-panel__body">
                <p class="ws-panel__lead">Paste before <code>&lt;/body&gt;</code> on every page. Updates live from the dashboard.</p>
                <pre id="embed-snippet" class="ws-code-block"><code>{{ $embedSnippet }}</code></pre>
            </div>
        </section>

        <section class="ws-panel">
            <header class="ws-panel__head">
                <h2 class="ws-panel__title">ChatFlow.init()</h2>
                <button type="button" class="ws-btn-ghost ws-btn-sm" data-copy-target="init-snippet">Copy</button>
            </header>
            <div class="ws-panel__body">
                <pre id="init-snippet" class="ws-code-block"><code>{{ $initSnippet }}</code></pre>
            </div>
        </section>

        <section class="ws-panel">
            <header class="ws-panel__head"><h2 class="ws-panel__title">Platform snippets</h2></header>
            <div class="ws-panel__body ws-stack">
                <div>
                    <p class="ws-label">WordPress</p>
                    <pre class="ws-code-block"><code>{{ $wordpressSnippet }}</code></pre>
                </div>
                <div>
                    <p class="ws-label">Shopify</p>
                    <pre class="ws-code-block"><code>{{ $shopifySnippet }}</code></pre>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy-target]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const el = document.getElementById(btn.dataset.copyTarget);
        const text = el?.innerText || '';
        navigator.clipboard.writeText(text).then(() => {
            const label = btn.dataset.copyTarget === 'embed-snippet' ? 'Copy embed code' : 'Copy';
            btn.textContent = 'Copied!';
            setTimeout(() => { btn.textContent = label; }, 2000);
        });
    });
});
</script>
@endpush
