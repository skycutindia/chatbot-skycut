@php
    $user = auth()->user();
    $isPlatform = $user->roleEnum()->isPlatformLevel();
    $demoHub = ! $isPlatform && $user->organization_id
        ? \App\Models\Website::where('organization_id', $user->organization_id)
            ->where('demo_slug', config('chatbot.demo_website_slug'))
            ->first()
        : null;
    $awaitingCount = $awaitingCount ?? 0;
    $dashModules = $dashModules ?? [];
@endphp
<aside id="dash-sidebar" class="dash-sidebar">
    <div class="dash-sidebar-header">
        <a href="{{ route('dashboard') }}" class="dash-logo">
            <span class="dash-logo-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="3"/></svg>
            </span>
            SkyCut
        </a>
        <p class="dash-sidebar-role">{{ $user->roleEnum()->label() }}</p>
    </div>

    <nav class="dash-sidebar-nav">
        <p class="dash-nav-section">Main</p>
        <a href="{{ route('dashboard') }}" class="dash-nav-link {{ request()->routeIs('dashboard') ? 'dash-nav-active' : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            Dashboard
        </a>

        @if($isPlatform)
            <a href="{{ route('admin.organizations.index') }}" class="dash-nav-link {{ request()->routeIs('admin.organizations.*') ? 'dash-nav-active' : '' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Organizations
            </a>
            <a href="{{ route('admin.settings.edit') }}" class="dash-nav-link {{ request()->routeIs('admin.settings.*') ? 'dash-nav-active' : '' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Platform settings
            </a>
        @else
            @if(($dashModules['websites'] ?? true) && $user->roleEnum()->canManageWebsites())
                <a href="{{ route('websites.index') }}" class="dash-nav-link {{ request()->routeIs('websites.*') ? 'dash-nav-active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    Websites
                </a>
            @endif

            <p class="dash-nav-section">Support</p>
            @if($user->roleEnum()->canHandleLiveChat())
                <a href="{{ route('inbox.index') }}" class="dash-nav-link {{ request()->routeIs('inbox.index') || request()->routeIs('inbox.queue') ? 'dash-nav-active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Live Inbox
                    @if($awaitingCount > 0)
                        <span class="ml-auto dash-badge dash-badge-warning">{{ $awaitingCount }}</span>
                    @endif
                </a>
                <a href="{{ route('inbox.archive') }}" class="dash-nav-link {{ request()->routeIs('inbox.archive') ? 'dash-nav-active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    Chat archive
                </a>
                @if($user->roleEnum()->canManageWebsites())
                <a href="{{ route('departments.index') }}" class="dash-nav-link {{ request()->routeIs('departments.*') ? 'dash-nav-active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Departments
                </a>
                <a href="{{ route('automation-rules.index') }}" class="dash-nav-link {{ request()->routeIs('automation-rules.*') ? 'dash-nav-active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Automation rules
                </a>
                @endif
            @endif
            @if(($dashModules['leads'] ?? true) && ! $user->roleEnum()->isReadOnly())
                <a href="{{ route('leads.index') }}" class="dash-nav-link {{ request()->routeIs('leads.*') ? 'dash-nav-active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Leads CRM
                </a>
                @if($dashModules['reports'] ?? true)
                <a href="{{ route('reports.index') }}" class="dash-nav-link {{ request()->routeIs('reports.*') ? 'dash-nav-active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Reports
                </a>
                @endif
            @endif

            <p class="dash-nav-section">Settings</p>
            @if($user->roleEnum()->canManageOrganization())
                <a href="{{ route('settings.index') }}" class="dash-nav-link {{ request()->routeIs('settings.index') || request()->routeIs('settings.ai.*') || request()->routeIs('settings.dashboard-modules.*') || request()->routeIs('settings.platform-ai.*') ? 'dash-nav-active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    AI &amp; settings
                </a>
            @endif
            @if($user->roleEnum()->canManageUsers() && ($dashModules['team'] ?? true))
                <a href="{{ route('team.index') }}" class="dash-nav-link {{ request()->routeIs('team.*') ? 'dash-nav-active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Team
                </a>
            @endif
            <a href="{{ route('settings.profile.edit') }}" class="dash-nav-link {{ request()->routeIs('settings.profile.*') ? 'dash-nav-active' : '' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                My profile
            </a>
        @endif
    </nav>

</aside>
