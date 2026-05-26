@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'dash-page-header']) }}>
    <div>
        @if($eyebrow)
            <p class="dash-page-eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="dash-page-title">{{ $title }}</h1>
        @if($subtitle)
            <p class="dash-page-sub">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="dash-page-header-actions cf-cluster">
            {{ $actions }}
        </div>
    @endif
</div>
