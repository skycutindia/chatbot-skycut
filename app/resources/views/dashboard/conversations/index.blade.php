@extends('layouts.app')

@section('title', 'Conversations')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Conversations</p>
        <h1 class="dash-page-title">Conversations</h1>
        <p class="dash-page-sub">{{ $website->name }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-medium">

    <ul class="dash-list mt-8">
        @forelse($conversations as $conv)
            <li>
                <a href="{{ route('websites.conversations.show', [$website, $conv]) }}" class="dash-list-item dash-card-hover">
                    <div>
                        <span class="font-medium">{{ $conv->visitor_name ?? 'Visitor' }} <span class="dash-muted text-sm">#{{ Str::limit($conv->visitor_id, 8) }}</span></span>
                        <p class="text-sm dash-muted mt-1">{{ $conv->last_message_at?->diffForHumans() }}</p>
                    </div>
                    <span class="dash-badge dash-badge-muted">{{ $conv->status }}</span>
                </a>
            </li>
        @empty
            <li class="dash-empty">No conversations yet.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $conversations->links() }}</div>
</div>
@endsection
