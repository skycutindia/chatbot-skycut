@extends('demo.layout', ['currentPage' => 'home'])

@section('title', 'Home')

@section('content')
<section class="demo-hero">
    <div class="demo-hero-inner">
        <div>
            <span class="demo-badge">Live AI demo</span>
            <h1>AI-Powered Customer Support That Never Sleeps</h1>
            <p class="demo-hero-lead">
                Deploy <strong>{{ $website->configuration->bot_name }}</strong> on your site in minutes.
                Pre-chat capture, live agent handoff, and a dashboard archive for every closed conversation.
            </p>
            <div class="demo-hero-actions">
                <button type="button" class="demo-btn demo-btn-primary" data-demo-open-chat>Open live chat</button>
                <a href="{{ route('demo.page', [$website->demo_slug, 'chatbot']) }}" class="demo-btn demo-btn-ghost">Configure chatbot</a>
            </div>
            <p class="demo-note" style="margin-top:1.5rem;">
                <strong>Try it:</strong> Use the floating chat button (bottom-right). Refresh the page for a new session. Close chat to archive in the dashboard.
            </p>
        </div>
        <div class="demo-chat-frame">
            @include('demo.partials.chat-preview')
        </div>
    </div>
</section>

<section class="demo-section">
    <div class="demo-section-inner">
        <div class="demo-section-head">
            <h2>Built for modern support teams</h2>
            <p>Everything you need to capture leads, automate answers, and escalate to humans.</p>
        </div>
        <div class="demo-grid-3">
            @foreach([
                ['AI responses', 'Trained on your knowledge base with confidence scoring.'],
                ['Pre-chat forms', 'Required name & phone; optional email & company sync to inbox.'],
                ['Chat archive', 'Closed chats move to dashboard archive — history stays for agents.'],
            ] as $item)
                <article class="demo-card">
                    <h3 style="font-weight:700;margin-bottom:0.5rem;">{{ $item[0] }}</h3>
                    <p style="color:#64748b;font-size:0.9375rem;line-height:1.55;">{{ $item[1] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endsection
