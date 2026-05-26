@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'dash-btn-' . $variant;
    $sizeClass = $size === 'sm' ? ' dash-btn-sm' : '';
    $class = trim($base . $sizeClass . ' ' . ($attributes->get('class') ?? ''));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
