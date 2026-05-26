@extends('layouts.app')

@section('title', 'Canned responses')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Live inbox</p>
        <h1 class="dash-page-title">Canned responses</h1>
        <p class="dash-page-sub">Quick replies for live agent conversations</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-medium">

    <ul class="dash-list mt-6">
        @foreach($responses as $response)
            <li class="dash-list-item">
                <div>
                    <p class="font-medium">{{ $response->title }} @if($response->shortcut)<span class="dash-muted text-xs">/{{ $response->shortcut }}</span>@endif</p>
                    <p class="text-sm dash-muted mt-1">{{ Str::limit($response->body, 120) }}</p>
                </div>
                <form method="POST" action="{{ route('canned-responses.destroy', $response) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="dash-btn-danger dash-btn-sm">Delete</button>
                </form>
            </li>
        @endforeach
    </ul>

    <form method="POST" action="{{ route('canned-responses.store') }}" class="dash-card mt-8">
        <div class="dash-card-body space-y-3">
            @csrf
            <h2 class="dash-form-section-title">Add response</h2>
            <div class="dash-field">
                <label class="dash-label" for="title">Title</label>
                <input id="title" name="title" placeholder="Title" required class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="shortcut">Shortcut (optional)</label>
                <input id="shortcut" name="shortcut" placeholder="Shortcut (optional)" class="dash-input w-full">
            </div>
            <div class="dash-field">
                <label class="dash-label" for="body">Response text</label>
                <textarea id="body" name="body" rows="3" placeholder="Response text..." required class="dash-textarea w-full"></textarea>
            </div>
            <button type="submit" class="dash-btn-primary">Add response</button>
        </div>
    </form>
</div>
@endsection
