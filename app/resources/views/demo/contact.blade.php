@extends('demo.layout', ['currentPage' => 'contact'])

@section('title', 'Contact')

@section('content')
<section class="demo-section" style="padding-top:4rem;">
    <div class="demo-section-inner" style="max-width:40rem;">
        <div class="demo-section-head" style="text-align:left;margin-bottom:2rem;">
            <h2>Contact us</h2>
            <p>Questions about the platform? Reach out or open the chat widget — every new browser session starts fresh after a page refresh.</p>
        </div>
        <form class="demo-config-panel" method="get" action="#" onsubmit="return false;">
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.35rem;">Name</label>
                <input type="text" class="demo-input" placeholder="Your name" style="width:100%;padding:0.625rem 0.75rem;border:1px solid #e2e8f0;border-radius:0.5rem;">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.35rem;">Email</label>
                <input type="email" class="demo-input" placeholder="you@company.com" style="width:100%;padding:0.625rem 0.75rem;border:1px solid #e2e8f0;border-radius:0.5rem;">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.35rem;">Message</label>
                <textarea rows="4" placeholder="How can we help?" style="width:100%;padding:0.625rem 0.75rem;border:1px solid #e2e8f0;border-radius:0.5rem;"></textarea>
            </div>
            <button type="button" class="demo-btn demo-btn-primary" onclick="alert('Demo form — use the chat widget for live support.')">Send message</button>
        </form>
        <p class="demo-note" style="margin-top:1.5rem;">
            Prefer live help? Use the <strong>chat bubble</strong> (bottom corner). When you close the chat, it is saved to the agent <strong>Chat archive</strong> in the dashboard.
        </p>
    </div>
</section>
@endsection
