@extends('demo.layout', ['currentPage' => 'pricing'])

@section('title', 'Pricing')

@section('content')
<section class="demo-section demo-section-alt" style="padding-top:4rem;">
    <div class="demo-section-inner">
        <div class="demo-section-head">
            <h2>Simple, Transparent Pricing</h2>
            <p>Start free and scale as you grow. No hidden fees.</p>
        </div>
        <div class="demo-grid-3">
            @foreach([
                ['Free', 'Perfect for trying out', '$0', ['100 chats/mo', '1 website', 'Basic AI', 'Pre-chat form'], false],
                ['Pro', 'Best for growing teams', '$49', ['5,000 chats/mo', '5 websites', 'Live handoff', 'Custom branding'], true],
                ['Enterprise', 'Large-scale operations', 'Custom', ['Unlimited chats', 'API access', 'SSO', '24/7 support'], false],
            ] as $tier)
                <article class="demo-card" style="{{ $tier[4] ? 'border-color:var(--demo-brand);box-shadow:0 16px 40px -16px rgba(13,148,136,0.25);' : '' }}">
                    @if($tier[4])
                        <span class="demo-badge" style="margin-bottom:0.75rem;">Most popular</span>
                    @endif
                    <h3 style="font-size:1.25rem;font-weight:700;">{{ $tier[0] }}</h3>
                    <p style="font-size:0.875rem;color:#64748b;">{{ $tier[1] }}</p>
                    <p style="margin-top:1.25rem;font-size:2.25rem;font-weight:800;">{{ $tier[2] }}@if($tier[2] !== 'Custom')<span style="font-size:1rem;font-weight:400;color:#94a3b8;">/mo</span>@endif</p>
                    <ul style="margin-top:1.5rem;padding:0;list-style:none;font-size:0.875rem;color:#64748b;display:flex;flex-direction:column;gap:0.5rem;">
                        @foreach($tier[3] as $f)
                            <li>✓ {{ $f }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('login') }}" class="demo-btn {{ $tier[4] ? 'demo-btn-primary' : 'demo-btn-ghost' }}" style="width:100%;margin-top:1.5rem;">Get started</a>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endsection
