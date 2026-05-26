{{-- Actions menu: bot sections + manage. Requires: $website --}}
@if(auth()->user()->roleEnum()->canManageWebsites())
    <p class="dash-actions-group-label">Bot workspace</p>
    <div class="dash-actions-sections-grid" role="group">
        @include('dashboard.websites.partials.workspace-sections', ['website' => $website])
    </div>

    <div class="dash-dropdown-divider"></div>
    <p class="dash-actions-group-label">Manage</p>
    <form method="POST" action="{{ route('websites.toggle-status', $website) }}" class="dash-actions-form" role="none">
        @csrf
        <button type="submit" class="dash-dropdown-item dash-dropdown-item-btn" role="menuitem">
            {{ $website->is_active ? 'Pause bot' : 'Activate bot' }}
        </button>
    </form>
    <form method="POST" action="{{ route('websites.duplicate', $website) }}" class="dash-actions-form" role="none" onsubmit="return confirm('Duplicate this bot?');">
        @csrf
        <button type="submit" class="dash-dropdown-item dash-dropdown-item-btn" role="menuitem">Duplicate bot</button>
    </form>
    <a href="{{ route('websites.embed.download', $website) }}" class="dash-dropdown-item" role="menuitem">Download plugin ZIP</a>
    <form method="POST" action="{{ route('websites.destroy', $website) }}" class="dash-actions-form" role="none" onsubmit="return confirm('Delete this website and all its data? This cannot be undone.');">
        @csrf @method('DELETE')
        <button type="submit" class="dash-dropdown-item dash-dropdown-item-btn dash-dropdown-item-danger" role="menuitem">Delete website</button>
    </form>
@endif
