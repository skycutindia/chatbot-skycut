@extends('demo.layout', ['currentPage' => 'features'])

@section('title', 'Features')

@section('content')
<section class="demo-section" style="padding-top:4rem;">
    <div class="demo-section-inner">
        <div class="demo-section-head">
            <h2>Everything You Need to Delight Customers</h2>
            <p>Powerful tools designed to transform your customer support experience.</p>
        </div>
        <div class="demo-grid-3">
            @foreach([
                ['AI Chatbot', 'Smart responses powered by your content. Human-like conversations with guardrails.'],
                ['Live Agent Handoff', 'Seamlessly transfer to humans with full visitor context.'],
                ['Knowledge Base', 'Train your bot with docs, FAQs, and website content.'],
                ['Analytics Dashboard', 'Track conversations, satisfaction, and AI performance.'],
                ['Pre-chat capture', 'Collect name, phone, email, and company before the first message.'],
                ['Custom Branding', 'Colors, avatar, welcome message, and widget position.'],
            ] as $feature)
                <article class="demo-card">
                    <h3 style="font-weight:700;margin-bottom:0.5rem;">{{ $feature[0] }}</h3>
                    <p style="color:#64748b;font-size:0.9375rem;line-height:1.55;">{{ $feature[1] }}</p>
                </article>
            @endforeach
        </div>
        <p style="text-align:center;margin-top:2.5rem;">
            <a href="{{ route('demo.page', [$website->demo_slug, 'chatbot']) }}" class="demo-btn demo-btn-primary">See live chatbot settings</a>
        </p>
    </div>
</section>
@endsection
