<div id="lc-config" hidden
    data-bulk-url="{{ route('inbox.bulk') }}"
    data-presence-url="{{ route('inbox.presence') }}"
    data-poll-url="{{ route('inbox.poll') }}"
    data-inbox-url="{{ route('inbox.index') }}"
    data-filter-presets-url="{{ route('inbox.filter-presets.index') }}"
    data-filter-presets-store-url="{{ route('inbox.filter-presets.store') }}"
    data-inbox-query="{{ http_build_query(request()->except('page')) }}"
    data-flash="{{ session('success') }}"
    data-can-write="{{ auth()->user()->roleEnum()->canHandleLiveChat() ? '1' : '0' }}"
    data-notify-icon="{{ asset('agent/icon-192.svg') }}"
></div>
<div id="lc-toast-root" class="lc-toast-root" aria-live="polite"></div>
