@extends('layouts.app')

@section('title', 'Websites')

@section('page-header')
<x-dash.page-header
    eyebrow="Workspace"
    title="Websites"
    subtitle="Manage chatbots, training, and plugins — each site is fully isolated."
/>
@endsection

@section('page-toolbar')
<x-dash.button variant="primary" size="sm" :href="route('websites.create')">+ New website</x-dash.button>
@endsection

@section('content')
@php
    $manageId = request()->integer('manage');
@endphp
<div
    class="dash-page ws-index-v3"
    id="websites-index"
    data-manage-website-id="{{ $manageId ?: '' }}"
>
    @if(isset($summary))
        <div class="ws-index-v3__kpis" role="group" aria-label="Organization summary">
            <div class="ws-kpi">
                <span class="ws-kpi__value">{{ $summary['total'] }}</span>
                <span class="ws-kpi__label">Websites</span>
            </div>
            <div class="ws-kpi ws-kpi--accent">
                <span class="ws-kpi__value">{{ $summary['active'] }}</span>
                <span class="ws-kpi__label">Active</span>
            </div>
            <div class="ws-kpi">
                <span class="ws-kpi__value">{{ number_format($summary['conversations']) }}</span>
                <span class="ws-kpi__label">Total chats</span>
            </div>
            <div class="ws-kpi">
                <span class="ws-kpi__value">{{ number_format($summary['leads']) }}</span>
                <span class="ws-kpi__label">Leads</span>
            </div>
            <div class="ws-kpi ws-kpi--warn">
                <span class="ws-kpi__value">{{ number_format($summary['open_chats']) }}</span>
                <span class="ws-kpi__label">Open now</span>
            </div>
        </div>
    @endif

    <div class="ws-index-v3__list" id="ws-website-list">
        @forelse($websites as $site)
            @include('dashboard.websites.partials.website-site-card', ['website' => $site])
        @empty
            <div class="ws-index-v3__empty">
                <div class="ws-index-v3__empty-icon" aria-hidden="true">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                </div>
                <h2 class="ws-index-v3__empty-title">No websites yet</h2>
                <p class="ws-index-v3__empty-text">Create your first chatbot in two steps — website details, then bot setup.</p>
                <a href="{{ route('websites.create') }}" class="ws-btn-primary">+ Create website</a>
            </div>
        @endforelse
    </div>

    @if($websites->hasPages())
        <div class="ws-index-v3__pagination">{{ $websites->links() }}</div>
    @endif
</div>
@endsection
