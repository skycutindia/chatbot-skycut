{{-- Card footer: Edit bot + overflow menu. Requires: $website --}}
@if(auth()->user()->roleEnum()->canManageWebsites())
    <div class="ws-site-card__toolbar">
        <a href="{{ route('websites.edit.bot', $website) }}" class="ws-site-card__edit-btn ws-site-card__edit-btn--primary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3z"/><path stroke-linecap="round" d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
            Edit bot
        </a>
        @include('dashboard.websites.partials.actions-menu', ['website' => $website])
    </div>
@endif
