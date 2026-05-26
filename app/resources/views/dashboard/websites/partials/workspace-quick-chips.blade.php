{{-- Horizontal quick links (responsive scroll). Requires: $website --}}
@if(auth()->user()->roleEnum()->canManageWebsites())
    <nav class="ws-site-card__chips" aria-label="Bot workspace for {{ $website->name }}">
        @include('dashboard.websites.partials.workspace-sections', [
            'website' => $website,
            'linkClass' => 'ws-chip',
        ])
    </nav>
@endif
