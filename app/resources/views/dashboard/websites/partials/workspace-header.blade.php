{{-- Workspace hero. Requires: $website, $title, $subtitle, optional $step (1|2), $stepLabel --}}
@php
    $c = $website->configuration;
    $stepNum = $step ?? null;
    $stepText = $stepLabel ?? ($stepNum === 1 ? 'Website' : ($stepNum === 2 ? 'Bot' : null));
@endphp
<header class="ws-workspace-header">
    <div class="ws-workspace-header__row">
    <div class="ws-workspace-header__identity">
        @if($website->logo_url)
            <img src="{{ $website->logo_url }}" alt="" class="ws-workspace-header__logo" width="48" height="48">
        @else
            <div class="ws-workspace-header__logo ws-workspace-header__logo--placeholder" aria-hidden="true">
                {{ strtoupper(substr($website->name, 0, 1)) }}
            </div>
        @endif
        <div class="ws-workspace-header__meta">
            <div class="ws-workspace-header__topline">
                @if($stepNum && $stepText)
                    <span class="ws-workspace-step">Step {{ $stepNum }} · {{ $stepText }}</span>
                @endif
                <span class="ws-workspace-status {{ $website->is_active ? 'is-live' : 'is-paused' }}">
                    {{ $website->is_active ? 'Live' : 'Paused' }}
                </span>
            </div>
            <h1 class="ws-workspace-header__site">{{ $website->name }}</h1>
            <p class="ws-workspace-header__bot">{{ $c?->bot_name ?? 'Chatbot' }}</p>
        </div>
    </div>
    @if(auth()->user()->roleEnum()->canManageWebsites())
        <div class="ws-workspace-header__actions">
            @include('dashboard.websites.partials.actions-menu', ['website' => $website])
        </div>
    @endif
    </div>
    <div class="ws-workspace-header__intro">
        <h2 class="ws-workspace-header__title">{{ $title }}</h2>
        @if(!empty($subtitle))
            <p class="ws-workspace-header__subtitle">{{ $subtitle }}</p>
        @endif
    </div>
</header>
