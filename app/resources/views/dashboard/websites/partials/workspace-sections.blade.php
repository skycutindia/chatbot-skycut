{{-- Shared bot workspace links. Requires: $website. Optional: $linkClass --}}
@php
    $unansweredCount = (int) ($website->unanswered_open_count ?? 0);
    $sections = [
        ['label' => 'Training', 'route' => 'websites.training.index', 'icon' => 'train'],
        ['label' => 'Q&A', 'route' => 'websites.questions.index', 'icon' => 'qa'],
        ['label' => 'Analytics', 'route' => 'websites.analytics', 'icon' => 'chart'],
        ['label' => 'Plugin', 'route' => 'websites.embed', 'icon' => 'plugin'],
        ['label' => 'Buttons', 'route' => 'websites.quick-actions.index', 'icon' => 'btn'],
        ['label' => 'Webhooks', 'route' => 'websites.webhooks.index', 'icon' => 'hook'],
        ['label' => 'Settings', 'route' => 'websites.edit', 'icon' => 'gear'],
    ];
    $linkClass = $linkClass ?? 'dash-actions-section-link';
@endphp
@foreach($sections as $section)
    <a href="{{ route($section['route'], $website) }}" class="{{ $linkClass }}" role="menuitem">
        {{ $section['label'] }}
        @if(!empty($section['badge']) && $section['badge'] > 0)
            <span class="dash-actions-badge">{{ $section['badge'] }}</span>
        @endif
    </a>
@endforeach
