@props([
    'type' => 'success',
])

@php
    $class = match ($type) {
        'error', 'danger' => 'dash-alert dash-alert-danger',
        'warning' => 'dash-alert dash-alert-warning',
        'info' => 'dash-alert dash-alert-info',
        default => 'dash-alert dash-alert-success',
    };
@endphp

<div {{ $attributes->merge(['class' => $class, 'role' => 'alert']) }}>
    {{ $slot }}
</div>
