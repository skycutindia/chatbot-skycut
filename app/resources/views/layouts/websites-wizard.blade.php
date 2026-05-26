{{-- Create-website wizard shell. Yields: ws-wizard-step (1|2), ws-title, ws-subtitle, workspace --}}
@extends('layouts.app')

@section('page-header')
<div class="ws-wizard-head">
    <a href="{{ route('websites.index') }}" class="ws-nav-v2__back">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
        Cancel
    </a>
    <ol class="ws-wizard-steps" aria-label="Create website progress">
        <li class="ws-wizard-step {{ (int) trim($__env->yieldContent('ws-wizard-step')) >= 1 ? 'is-done' : '' }} {{ trim($__env->yieldContent('ws-wizard-step')) === '1' ? 'is-current' : '' }}">
            <span class="ws-wizard-step__num">1</span>
            <span class="ws-wizard-step__label">Website</span>
        </li>
        <li class="ws-wizard-step__line" aria-hidden="true"></li>
        <li class="ws-wizard-step {{ trim($__env->yieldContent('ws-wizard-step')) === '2' ? 'is-current' : '' }}">
            <span class="ws-wizard-step__num">2</span>
            <span class="ws-wizard-step__label">Bot</span>
        </li>
    </ol>
</div>
@endsection

@section('content')
<div class="ws-wizard">
    <header class="ws-wizard-intro">
        <h1 class="ws-wizard-intro__title">@yield('ws-title')</h1>
        @if(trim($__env->yieldContent('ws-subtitle')))
            <p class="ws-wizard-intro__sub">@yield('ws-subtitle')</p>
        @endif
    </header>
    <div class="ws-wizard-body">
        @yield('workspace')
    </div>
</div>
@endsection
