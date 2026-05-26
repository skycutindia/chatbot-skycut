@php
    $authUser = auth()->user();
    $initials = collect(explode(' ', $authUser->name))->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('');
    $awaitingCount = $awaitingCount ?? 0;
    $mentionCount = $mentionCount ?? 0;
    $authRole = $authUser->roleEnum();
    $authOrg = $authUser->organization ?? null;
@endphp
<header class="dash-header">
    <button type="button" id="mobile-nav-toggle" class="dash-icon-btn lg:hidden" aria-label="Menu">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    @hasSection('page-header')
        <div class="dash-header-page">
            @yield('page-header')
        </div>
    @endif

    <div class="dash-header-right">
        <div class="dash-global-search" id="global-search-wrap">
            <input
                id="global-search"
                type="text"
                placeholder="Search conversations, leads, websites..."
                value="{{ request('q') }}"
                class="dash-input dash-global-search-input"
                autocomplete="off"
                aria-label="Search conversations, leads, websites"
            >
            <div id="search-results" class="dash-search-results hidden"></div>
        </div>

        <div class="dash-header-actions">
        @if(auth()->user()->roleEnum()->canHandleLiveChat())
            <div class="relative">
                <button type="button" id="mention-menu-button" class="dash-icon-btn relative" title="Mentions"
                    data-mentions-url="{{ route('inbox.mentions.index') }}"
                    data-mentions-read-url="{{ route('inbox.mentions.read') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                    @if($mentionCount > 0)
                        <span class="dash-notify-dot">{{ $mentionCount > 9 ? '9+' : $mentionCount }}</span>
                    @endif
                </button>
                <div id="mention-menu-panel" class="dash-dropdown hidden absolute right-0 mt-2 w-80 py-1 z-50">
                    <div class="px-4 py-2 border-b border-[var(--dash-border)]">
                        <p class="text-sm font-semibold">Mentions</p>
                    </div>
                    <div id="mention-menu-list" class="max-h-72 overflow-y-auto"></div>
                </div>
            </div>
            <a href="{{ route('inbox.index') }}" class="dash-icon-btn relative" title="Live inbox">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                @if($awaitingCount > 0)
                    <span class="dash-notify-dot">{{ $awaitingCount > 9 ? '9+' : $awaitingCount }}</span>
                @endif
            </a>
        @endif

        <button type="button" id="theme-toggle" class="dash-icon-btn" title="Toggle light/dark theme" aria-label="Toggle light/dark theme">
            <svg class="dash-theme-icon dash-theme-icon-light" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <svg class="dash-theme-icon dash-theme-icon-dark hidden" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>

        <div class="relative dash-user-wrap">
            <button type="button" id="user-menu-button" class="dash-user-btn" aria-haspopup="true" aria-expanded="false" aria-controls="user-menu">
                <span class="dash-user-avatar" aria-hidden="true">{{ $initials }}</span>
                <span class="dash-user-meta hidden md:block">
                    <span class="dash-user-name">{{ $authUser->name }}</span>
                    <span class="dash-user-role">{{ $authRole->label() }}</span>
                </span>
                <svg class="dash-user-chevron hidden md:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <div id="user-menu" class="dash-dropdown dash-user-menu hidden absolute right-0 mt-2 z-50" role="menu" aria-labelledby="user-menu-button">
                <div class="dash-user-menu__identity">
                    <span class="dash-user-menu__avatar">{{ $initials }}</span>
                    <div class="dash-user-menu__identity-text">
                        <p class="dash-user-menu__name">{{ $authUser->name }}</p>
                        <p class="dash-user-menu__email" title="{{ $authUser->email }}">{{ $authUser->email }}</p>
                        <p class="dash-user-menu__badges">
                            <span class="dash-user-menu__badge dash-user-menu__badge--role">{{ $authRole->label() }}</span>
                            @if($authOrg)
                                <span class="dash-user-menu__badge dash-user-menu__badge--org" title="{{ $authOrg->name }}">{{ Str::limit($authOrg->name, 22) }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="dash-user-menu__section">
                    <a href="{{ route('settings.profile.edit') }}" class="dash-user-menu__item" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 14a4 4 0 10-8 0M5 21a7 7 0 0114 0M12 11a3 3 0 100-6 3 3 0 000 6z"/></svg>
                        <span class="dash-user-menu__item-text">
                            <span>My profile</span>
                            <small>Name, password, avatar</small>
                        </span>
                    </a>
                    <a href="{{ route('settings.two-factor.show') }}" class="dash-user-menu__item" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11c-1.1 0-2 .9-2 2v3a2 2 0 104 0v-3c0-1.1-.9-2-2-2zM8 11V8a4 4 0 118 0v3M5 11h14v9H5z"/></svg>
                        <span class="dash-user-menu__item-text">
                            <span>Security</span>
                            <small>Two-factor authentication</small>
                        </span>
                    </a>
                </div>

                @if($authRole->canManageOrganization() || $authRole->canManageUsers())
                <div class="dash-user-menu__section">
                    @if($authRole->canManageOrganization())
                        <a href="{{ route('settings.index') }}" class="dash-user-menu__item" role="menuitem">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="dash-user-menu__item-text">
                                <span>Workspace settings</span>
                                <small>AI, branding, integrations</small>
                            </span>
                        </a>
                    @endif
                    @if($authRole->canManageUsers())
                        <a href="{{ route('team.index') }}" class="dash-user-menu__item" role="menuitem">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6 5.87v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2m7-8a4 4 0 100-8 4 4 0 000 8zm6 0a3 3 0 100-6 3 3 0 000 6z"/></svg>
                            <span class="dash-user-menu__item-text">
                                <span>Team members</span>
                                <small>Invite and manage roles</small>
                            </span>
                        </a>
                    @endif
                </div>
                @endif

                <div class="dash-user-menu__section">
                    <div class="dash-user-menu__theme" role="group" aria-label="Theme">
                        <span class="dash-user-menu__theme-label">Appearance</span>
                        <div class="dash-user-menu__theme-options" id="user-theme-options">
                            <button type="button" class="dash-user-menu__theme-btn" data-theme="light" title="Light">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                <span>Light</span>
                            </button>
                            <button type="button" class="dash-user-menu__theme-btn" data-theme="dark" title="Dark">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                                <span>Dark</span>
                            </button>
                            <button type="button" class="dash-user-menu__theme-btn" data-theme="system" title="System">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v2m6-2v2M5 17h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v9a2 2 0 002 2z"/></svg>
                                <span>System</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="dash-user-menu__section">
                    <a href="{{ route('dashboard') }}" class="dash-user-menu__item" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6"/></svg>
                        <span class="dash-user-menu__item-text"><span>Dashboard home</span></span>
                    </a>
                    <button type="button" class="dash-user-menu__item" id="user-menu-shortcuts" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8h18M3 16h18M7 4v16m10-16v16"/></svg>
                        <span class="dash-user-menu__item-text">
                            <span>Keyboard shortcuts</span>
                            <small>Press <kbd>?</kbd></small>
                        </span>
                    </button>
                </div>

                <div class="dash-user-menu__footer">
                    <form method="POST" action="{{ route('logout') }}" class="m-0 w-full">
                        @csrf
                        <button type="submit" class="dash-user-menu__signout">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span>Sign out</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Keyboard shortcuts modal --}}
        <div class="dash-shortcuts-modal hidden" id="dash-shortcuts-modal" role="dialog" aria-modal="true" aria-labelledby="dash-shortcuts-title">
            <div class="dash-shortcuts-modal__panel">
                <div class="dash-shortcuts-modal__head">
                    <h3 id="dash-shortcuts-title">Keyboard shortcuts</h3>
                    <button type="button" class="dash-shortcuts-modal__close" data-shortcuts-close aria-label="Close">×</button>
                </div>
                <div class="dash-shortcuts-modal__body">
                    <div class="dash-shortcut-row"><span>Open profile menu</span><kbd>Shift</kbd> <kbd>P</kbd></div>
                    <div class="dash-shortcut-row"><span>Focus global search</span><kbd>/</kbd></div>
                    <div class="dash-shortcut-row"><span>Open live inbox</span><kbd>G</kbd> <kbd>I</kbd></div>
                    <div class="dash-shortcut-row"><span>Go to dashboard</span><kbd>G</kbd> <kbd>D</kbd></div>
                    <div class="dash-shortcut-row"><span>Toggle light/dark theme</span><kbd>T</kbd></div>
                    <div class="dash-shortcut-row"><span>Show this dialog</span><kbd>?</kbd></div>
                    <div class="dash-shortcut-row"><span>Close menu / modal</span><kbd>Esc</kbd></div>
                </div>
            </div>
        </div>
        </div>
    </div>
</header>
