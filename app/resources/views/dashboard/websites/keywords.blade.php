@extends('layouts.websites-workspace')

@section('title', 'Keywords — '.$website->name)

@section('ws-tab', 'keywords')
@section('ws-title', 'Trigger keywords')
@section('ws-subtitle', 'Smart matching — updates live without reinstalling the plugin.')

@section('workspace')
    <form method="POST" action="{{ route('websites.keywords.store', $website) }}" class="ws-panel">
        <div class="dash-card-header"><h2 class="font-semibold">Add keyword</h2></div>
        <div class="dash-card-body grid md:grid-cols-2 gap-4">
            @csrf
            <div class="dash-field">
                <label class="dash-label">Keyword / phrase</label>
                <input type="text" name="keyword" required class="dash-input w-full" placeholder="pricing">
            </div>
            <div class="dash-field md:col-span-2">
                <label class="dash-label">Bot response</label>
                <textarea name="response" rows="3" required class="dash-textarea w-full"></textarea>
            </div>
            <button type="submit" class="dash-btn-primary md:col-span-2">Save keyword</button>
        </div>
    </form>

    <ul class="dash-list mt-6">
        @forelse($keywords as $kw)
            <li class="dash-list-item flex justify-between items-start gap-4">
                <div>
                    <strong>{{ $kw->keyword }}</strong>
                    <p class="text-sm dash-muted mt-1">{{ Str::limit($kw->response, 160) }}</p>
                </div>
                <form method="POST" action="{{ route('websites.keywords.destroy', [$website, $kw]) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="dash-btn-ghost dash-btn-sm text-red-600">Remove</button>
                </form>
            </li>
        @empty
            <li class="dash-muted py-4">No trigger keywords yet.</li>
        @endforelse
    </ul>
@endsection
