@php
    $queueStats = $queueStats ?? ['awaiting' => 0, 'open' => 0, 'mine' => 0];
    $inboxBaseQuery = request()->except(['awaiting', 'assigned', 'page']);
    $awaitingActive = request()->boolean('awaiting');
    $mineActive = request('assigned') === 'me';
    $openActive = ! $awaitingActive && ! $mineActive && ! request('starred') && ! request('pinned') && request()->routeIs('inbox.index');
    $presence = auth()->user()->agentStatus?->status ?? 'online';
    $presenceClass = match ($presence) {
        'away' => 'is-away',
        'busy' => 'is-busy',
        'offline' => 'is-offline',
        default => 'is-online',
    };
@endphp

<nav class="lc-toolbar" aria-label="Inbox navigation">
    <div class="lc-toolbar-presence">
        <span class="lc-presence-dot {{ $presenceClass }}" aria-hidden="true"></span>
        <select id="lc-presence" class="lc-presence-select" aria-label="Your status">
            <option value="online" @selected($presence === 'online')>Online</option>
            <option value="away" @selected($presence === 'away')>Away</option>
            <option value="busy" @selected($presence === 'busy')>Busy</option>
            <option value="offline" @selected($presence === 'offline')>Offline</option>
        </select>
    </div>

    <div class="lc-toolbar-filters">
        <a href="{{ route('inbox.index', array_merge($inboxBaseQuery, ['awaiting' => 1])) }}"
           class="lc-chip {{ $awaitingActive ? 'is-active' : '' }}">
            Awaiting <strong data-lc-stat="awaiting">{{ $queueStats['awaiting'] }}</strong>
        </a>
        <a href="{{ route('inbox.index', $inboxBaseQuery) }}"
           class="lc-chip {{ $openActive ? 'is-active' : '' }}">
            All open <strong data-lc-stat="open">{{ $queueStats['open'] }}</strong>
        </a>
        <a href="{{ route('inbox.index', array_merge($inboxBaseQuery, ['assigned' => 'me'])) }}"
           class="lc-chip {{ $mineActive ? 'is-active' : '' }}">
            Mine <strong data-lc-stat="mine">{{ $queueStats['mine'] }}</strong>
        </a>
    </div>

    <div class="lc-toolbar-tabs">
        <a href="{{ route('inbox.index', request()->only(['conversation', 'website_id', 'department_id', 'q', 'sort', 'awaiting', 'assigned', 'starred', 'pinned'])) }}"
           class="lc-tab {{ request()->routeIs('inbox.index') ? 'is-active' : '' }}">Inbox</a>
        <a href="{{ route('inbox.queue') }}" class="lc-tab {{ request()->routeIs('inbox.queue') ? 'is-active' : '' }}">Queue</a>
        <a href="{{ route('inbox.archive') }}" class="lc-tab {{ request()->routeIs('inbox.archive') ? 'is-active' : '' }}">Archive</a>
    </div>
</nav>
