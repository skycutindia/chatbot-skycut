@props([
    'title' => null,
    'subtitle' => null,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'dash-card' . ($padding ? '' : ' dash-card-flush')]) }}>
    @if($title || $subtitle)
        <div class="dash-card-header">
            @if($title)<h2 class="dash-card-title">{{ $title }}</h2>@endif
            @if($subtitle)<p class="dash-card-sub">{{ $subtitle }}</p>@endif
        </div>
    @endif
    <div>
        {{ $slot }}
    </div>
</div>
