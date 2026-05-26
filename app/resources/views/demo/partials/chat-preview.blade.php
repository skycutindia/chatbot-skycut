{{-- Static hero preview + opens floating widget --}}
<div class="demo-chat-preview">
    <div class="demo-chat-preview-head">
        <span class="demo-chat-preview-avatar" style="background:linear-gradient(135deg,{{ $website->configuration->primary_color ?? '#0d9488' }},{{ $website->configuration->secondary_color ?? '#14b8a6' }});">
            <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="3"/></svg>
        </span>
        <div>
            <strong>{{ $website->configuration->bot_name ?? 'Assistant' }}</strong>
            <span class="demo-chat-preview-status"><span class="demo-status-dot"></span> Online</span>
        </div>
    </div>
    <div class="demo-chat-preview-messages">
        <div class="demo-chat-preview-bubble is-bot">{{ Str::limit($website->configuration->welcome_message ?? 'Hi! How can we help you today?', 120) }}</div>
        <div class="demo-chat-preview-bubble is-user">I need help with pricing</div>
        <div class="demo-chat-preview-bubble is-bot">Happy to help — or connect you with a live agent.</div>
    </div>
    <button type="button" class="demo-btn demo-btn-primary demo-open-chat-btn" data-demo-open-chat>
        Open live chat
    </button>
    <p class="demo-chat-preview-hint">Use the floating chat button at the bottom-right on every page.</p>
</div>
