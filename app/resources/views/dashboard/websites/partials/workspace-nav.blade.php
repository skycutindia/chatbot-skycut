@php
    $user = auth()->user();
    $canManage = $user->roleEnum()->canManageWebsites();
    $canInbox = $user->roleEnum()->canHandleLiveChat();
    $active = $workspaceTab ?? 'settings';
    $unansweredBadge = (int) ($website->unanswered_open_count
        ?? $website->unansweredQuestions()->where('status', 'open')->count());

    $groups = [
        [
            'label' => 'Setup',
            'tabs' => [
                ['id' => 'settings', 'label' => 'Website', 'route' => 'websites.edit', 'show' => $canManage],
                ['id' => 'bot', 'label' => 'Bot', 'route' => 'websites.edit.bot', 'show' => $canManage],
                ['id' => 'embed', 'label' => 'Plugin', 'route' => 'websites.embed', 'show' => $canManage],
                ['id' => 'advanced', 'label' => 'Advanced', 'route' => 'websites.advanced', 'show' => $canManage],
            ],
        ],
        [
            'label' => 'Training',
            'tabs' => [
                ['id' => 'training', 'label' => 'Training', 'route' => 'websites.training.index', 'show' => $canManage, 'badge' => $unansweredBadge],
                ['id' => 'questions', 'label' => 'Q&A', 'route' => 'websites.questions.index', 'show' => $canManage],
                ['id' => 'quick-actions', 'label' => 'Buttons', 'route' => 'websites.quick-actions.index', 'show' => $canManage],
            ],
        ],
        [
            'label' => 'Insights',
            'tabs' => [
                ['id' => 'analytics', 'label' => 'Analytics', 'route' => 'websites.analytics', 'show' => $canManage],
                ['id' => 'webhooks', 'label' => 'Webhooks', 'route' => 'websites.webhooks.index', 'show' => $canManage],
                ['id' => 'inbox', 'label' => 'Inbox', 'route' => 'inbox.index', 'show' => $canInbox, 'params' => ['website_id' => $website->id]],
            ],
        ],
    ];
@endphp
<nav class="ws-nav-v2" data-ws-nav aria-label="Sections for {{ $website->name }}">
    <a href="{{ route('websites.index') }}" class="ws-nav-v2__back">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
        All websites
    </a>
    <div class="ws-nav-v2__scroll" tabindex="0">
        @foreach($groups as $group)
            @php
                $visibleTabs = collect($group['tabs'])->filter(fn ($t) => $t['show'])->values();
            @endphp
            @if($visibleTabs->isNotEmpty())
                <div class="ws-nav-v2__group">
                    <span class="ws-nav-v2__group-label">{{ $group['label'] }}</span>
                    <div class="ws-nav-v2__pills">
                        @foreach($visibleTabs as $tab)
                            <a
                                href="{{ route($tab['route'], $tab['params'] ?? $website) }}"
                                class="ws-nav-v2__pill {{ $active === $tab['id'] ? 'is-active' : '' }}"
                            >
                                {{ $tab['label'] }}
                                @if(!empty($tab['badge']) && $tab['badge'] > 0)
                                    <span class="ws-nav-v2__badge">{{ $tab['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</nav>
