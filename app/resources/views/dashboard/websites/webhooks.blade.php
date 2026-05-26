@extends('layouts.websites-workspace')

@section('title', 'Webhooks — '.$website->name)

@section('ws-tab', 'webhooks')
@section('ws-title', 'Webhooks')
@section('ws-subtitle', 'Receive lead, chat, and close events at your endpoint.')

@section('workspace')
    <form method="POST" action="{{ route('websites.webhooks.store', $website) }}" class="ws-panel">
        <div class="dash-card-header"><h2 class="font-semibold">Add webhook</h2></div>
        <div class="dash-card-body space-y-4">
            @csrf
            <div class="dash-field">
                <label class="dash-label">Name</label>
                <input type="text" name="name" required class="dash-input w-full" placeholder="CRM sync">
            </div>
            <div class="dash-field">
                <label class="dash-label">Endpoint URL</label>
                <input type="url" name="url" required class="dash-input w-full" placeholder="https://your-app.com/webhooks/chatbot">
            </div>
            <div class="dash-field">
                <span class="dash-label">Events</span>
                <div class="space-y-2 mt-2">
                    @foreach($eventOptions as $key => $label)
                        <label class="dash-checkbox-row">
                            <input type="checkbox" name="events[]" value="{{ $key }}">
                            {{ $label }} (<code class="text-xs">{{ $key }}</code>)
                        </label>
                    @endforeach
                </div>
            </div>
            <button type="submit" class="dash-btn-primary">Save webhook</button>
        </div>
    </form>

    <ul class="dash-list mt-6">
        @forelse($webhooks as $hook)
            <li class="dash-list-item flex flex-col sm:flex-row sm:justify-between gap-3">
                <div>
                    <strong>{{ $hook->name }}</strong>
                    <p class="text-xs dash-muted break-all">{{ $hook->url }}</p>
                    <p class="text-xs mt-1">{{ implode(', ', $hook->events ?? []) }}</p>
                    @if($hook->secret)
                        <p class="text-xs dash-muted mt-1">Secret: <code>{{ $hook->secret }}</code></p>
                    @endif
                </div>
                <form method="POST" action="{{ route('websites.webhooks.destroy', [$website, $hook]) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="dash-btn-ghost dash-btn-sm text-red-600">Remove</button>
                </form>
            </li>
        @empty
            <li class="dash-muted py-4">No webhooks configured.</li>
        @endforelse
    </ul>
@endsection
