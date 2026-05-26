{{-- Website list card. Requires: $website --}}
@php
    $botName = $website->configuration?->bot_name ?? '—';
    $activity = $website->conversations_max_last_message_at
        ? \Carbon\Carbon::parse($website->conversations_max_last_message_at)->diffForHumans()
        : '—';
    $canManage = auth()->user()->roleEnum()->canManageWebsites();
    $searchBlob = strtolower(implode(' ', array_filter([
        $website->name,
        $botName,
        $website->url,
        $website->is_active ? 'active' : 'paused',
    ])));
@endphp
<article
    class="ws-site-card"
    id="website-card-{{ $website->id }}"
    data-website-id="{{ $website->id }}"
    data-search="{{ e($searchBlob) }}"
>
    <div class="ws-site-card__layout">
        <div class="ws-site-card__rail" aria-hidden="true">
            @if($website->logo_url)
                <img src="{{ $website->logo_url }}" alt="" class="ws-site-card__logo" width="40" height="40" loading="lazy">
            @else
                <span class="ws-site-card__logo ws-site-card__logo--ph">{{ strtoupper(substr($website->name, 0, 1)) }}</span>
            @endif
        </div>

        <div class="ws-site-card__main">
            <header class="ws-site-card__head">
                <div class="ws-site-card__intro">
                    <h2 class="ws-site-card__name">
                        <a href="{{ route('websites.edit.bot', $website) }}" class="ws-site-card__name-link">{{ $website->name }}</a>
                    </h2>
                    <p class="ws-site-card__bot">{{ $botName }}</p>
                    @if($website->url)
                        <a href="{{ $website->url }}" target="_blank" rel="noopener noreferrer" class="ws-site-card__url">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path stroke-linecap="round" d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                            {{ preg_replace('#^https?://#', '', $website->url) }}
                        </a>
                    @endif
                </div>
                <div class="ws-site-card__badges">
                    <span class="ws-site-card__status {{ $website->is_active ? 'is-live' : 'is-paused' }}">
                        <span class="ws-site-card__status-dot"></span>
                        {{ $website->is_active ? 'Active' : 'Paused' }}
                    </span>
                    <time class="ws-site-card__created" datetime="{{ $website->created_at->toDateString() }}">
                        {{ $website->created_at->format('M j, Y') }}
                    </time>
                </div>
            </header>

            <dl class="ws-site-card__metrics">
                <div class="ws-site-card__metric">
                    <dt>Chats</dt>
                    <dd>{{ number_format($website->conversations_count) }}</dd>
                </div>
                <div class="ws-site-card__metric">
                    <dt>Open</dt>
                    <dd>{{ number_format($website->open_chats_count ?? 0) }}</dd>
                </div>
                <div class="ws-site-card__metric">
                    <dt>Leads</dt>
                    <dd>{{ number_format($website->leads_count) }}</dd>
                </div>
                <div class="ws-site-card__metric">
                    <dt>Activity</dt>
                    <dd class="is-text">{{ $activity }}</dd>
                </div>
            </dl>
        </div>

        @if($canManage)
        <div class="ws-site-card__action">
            <a href="{{ route('websites.edit.bot', $website) }}" class="ws-site-card__cta">
                <span class="ws-site-card__cta-label">Edit bot</span>
                <span class="ws-site-card__cta-arrow" aria-hidden="true">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </a>
        </div>
        @endif
    </div>
</article>
