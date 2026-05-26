{{-- Live widget preview. Requires: $website; uses #bot-preview-* ids for JS. --}}
@php $c = $website->configuration; @endphp
<aside class="ws-bot-preview" aria-label="Widget preview">
    <p class="ws-bot-preview__label">Live preview</p>
    <div class="ws-bot-preview__frame">
        <div class="ws-bot-preview__widget" id="bot-preview-widget" style="--preview-primary: {{ $c->primary_color ?? '#0d9488' }}; --preview-secondary: {{ $c->secondary_color ?? '#14b8a6' }};">
            <div class="ws-bot-preview__header">
                @if($c->avatar_url)
                    <img src="{{ $c->avatar_url }}" alt="" class="ws-bot-preview__avatar" id="bot-preview-avatar-img" width="32" height="32">
                @endif
                <span class="ws-bot-preview__avatar ws-bot-preview__avatar--fallback" id="bot-preview-avatar-fallback" @if($c->avatar_url) hidden @endif>AI</span>
                <div>
                    <strong id="bot-preview-name">{{ $c->bot_name ?? 'Assistant' }}</strong>
                    <span class="ws-bot-preview__status" id="bot-preview-status">{{ ($c->bot_online ?? true) ? 'Online' : 'Offline' }}</span>
                </div>
            </div>
            <div class="ws-bot-preview__bubble" id="bot-preview-welcome">
                {{ $c->welcome_message ?? 'Hi! How can we help you today?' }}
            </div>
            <div class="ws-bot-preview__typing" id="bot-preview-typing" @if(!($c->typing_animation ?? true)) hidden @endif>
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>
    <p class="ws-bot-preview__hint">Changes apply to the live widget after you save.</p>
</aside>
