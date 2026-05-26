@php
    $menuId = 'website-actions-'.$website->id;
    $templateId = 'website-sheet-'.$website->id.'-template';
@endphp
<div class="ws-actions-menu dash-actions-menu" data-actions-menu>
    <button
        type="button"
        class="ws-actions-trigger"
        data-actions-trigger
        data-actions-panel-id="{{ $menuId }}"
        data-actions-template-id="{{ $templateId }}"
        data-website-name="{{ $website->name }}"
        aria-haspopup="menu"
        aria-expanded="false"
        aria-controls="{{ $menuId }}"
        aria-label="Open menu for {{ $website->name }}"
        title="Menu"
    >
        <svg class="ws-actions-trigger-icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <circle cx="12" cy="5" r="1.75"/><circle cx="12" cy="12" r="1.75"/><circle cx="12" cy="19" r="1.75"/>
        </svg>
    </button>
    <div
        id="{{ $menuId }}"
        class="dash-dropdown dash-actions-panel ws-actions-panel hidden"
        role="menu"
        data-actions-panel
        aria-label="Menu for {{ $website->name }}"
    >
        @include('dashboard.websites.partials.action-links', ['website' => $website])
    </div>
    <template id="{{ $templateId }}">
        @include('dashboard.websites.partials.action-links', ['website' => $website])
    </template>
</div>
