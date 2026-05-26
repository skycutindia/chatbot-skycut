{{-- Shared shell for per-website workspace pages. Yields: ws-tab, ws-title, ws-subtitle, ws-step, ws-step-label, workspace, page-toolbar --}}
@extends('layouts.app')

@section('page-header')
<nav class="ws-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('websites.index') }}" class="ws-breadcrumb__link">Websites</a>
    <span class="ws-breadcrumb__sep" aria-hidden="true">/</span>
    <a href="{{ route('websites.edit', $website) }}" class="ws-breadcrumb__link">{{ $website->name }}</a>
    <span class="ws-breadcrumb__sep" aria-hidden="true">/</span>
    <span class="ws-breadcrumb__current">@yield('ws-title')</span>
</nav>
@endsection

@section('content')
<div class="ws-workspace" data-ws-workspace>
    @include('dashboard.websites.partials.workspace-header', [
        'website' => $website,
        'title' => trim($__env->yieldContent('ws-title')),
        'subtitle' => trim($__env->yieldContent('ws-subtitle')) ?: null,
        'step' => $__env->hasSection('ws-step') ? (int) trim($__env->yieldContent('ws-step')) : null,
        'stepLabel' => $__env->hasSection('ws-step-label') ? trim($__env->yieldContent('ws-step-label')) : null,
    ])

    @include('dashboard.websites.partials.workspace-nav', [
        'website' => $website,
        'workspaceTab' => trim($__env->yieldContent('ws-tab')) ?: 'settings',
    ])

    <div class="ws-page">
        @if(session('success'))
            <div class="ws-alert ws-alert--success" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="ws-alert ws-alert--danger" role="alert">{{ session('error') }}</div>
        @endif
        @yield('workspace')
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const nav = document.querySelector('[data-ws-nav]');
    if (!nav) return;
    const active = nav.querySelector('.ws-nav-v2__pill.is-active');
    if (active) {
        requestAnimationFrame(() => {
            active.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
        });
    }
})();
</script>
@endpush
