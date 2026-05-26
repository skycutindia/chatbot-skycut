@extends('layouts.app')

@section('title', $website->name)

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Websites</p>
        <h1 class="dash-page-title">{{ $website->name }}</h1>
    </div>
</div>
@endsection

@section('page-toolbar')
<div class="flex gap-2 flex-wrap">
    <a href="{{ route('websites.edit', $website) }}" class="dash-btn-primary dash-btn-sm">Settings</a>
    <a href="{{ route('websites.knowledge.index', $website) }}" class="dash-btn-secondary dash-btn-sm">Knowledge</a>
    <a href="{{ route('websites.knowledge.training', $website) }}" class="dash-btn-secondary dash-btn-sm">Train</a>
    <a href="{{ route('websites.conversations.index', $website) }}" class="dash-btn-secondary dash-btn-sm">Conversations</a>
    <a href="{{ route('websites.embed', $website) }}" class="dash-btn-secondary dash-btn-sm">Embed code</a>
    <a href="{{ route('websites.quick-actions.index', $website) }}" class="dash-btn-secondary dash-btn-sm">Quick actions</a>
    <a href="{{ route('websites.analytics', $website) }}" class="dash-btn-secondary dash-btn-sm">Analytics</a>
</div>
@endsection

@section('content')
<div class="dash-page-medium">

    <div class="dash-card mt-8">
        <div class="dash-card-header">
            <h2 class="font-semibold text-lg">Embed snippet</h2>
        </div>
        <div class="dash-card-body">
            <p class="text-sm dash-muted">Paste before closing <code>&lt;/body&gt;</code>. All content loads dynamically from the API.</p>
            <pre class="mt-4 p-4 rounded-lg bg-[var(--dash-surface-2)] text-emerald-600 text-sm overflow-x-auto"><code>{{ $embedSnippet }}</code></pre>
        </div>
    </div>

    <div class="mt-8 grid md:grid-cols-2 gap-6">
        <div class="dash-card">
            <div class="dash-card-header">
                <h3 class="font-semibold">Appearance</h3>
            </div>
            <div class="dash-card-body">
                <ul class="text-sm dash-muted space-y-1">
                    <li>Bot: {{ $website->configuration->bot_name }}</li>
                    <li>Theme: {{ $website->configuration->theme_mode }}</li>
                    <li>Position: {{ $website->configuration->position }}</li>
                    <li>Colors: {{ $website->configuration->primary_color }} / {{ $website->configuration->secondary_color }}</li>
                </ul>
            </div>
        </div>
        <div class="dash-card">
            <div class="dash-card-header">
                <h3 class="font-semibold">AI</h3>
            </div>
            <div class="dash-card-body">
                <ul class="text-sm dash-muted space-y-1">
                    <li>Model: {{ $website->configuration->ai_model }}</li>
                    <li>Temperature: {{ $website->configuration->ai_temperature }}</li>
                    <li>Confidence: {{ $website->configuration->confidence_threshold }}</li>
                    <li>AI enabled: {{ $website->configuration->ai_enabled ? 'Yes' : 'No' }}</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="dash-card mt-8">
        <div class="dash-card-header">
            <h3 class="font-semibold">Suggested questions</h3>
        </div>
        <div class="dash-card-body">
            <ul class="dash-list">
                @foreach($website->suggestedQuestions as $q)
                    <li class="dash-list-item">
                        <span>{{ $q->question }}</span>
                        <form method="POST" action="{{ route('websites.questions.destroy', [$website, $q]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="dash-btn-danger dash-btn-sm">Remove</button>
                        </form>
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('websites.questions.store', $website) }}" class="mt-4 flex gap-2">
                @csrf
                <input type="text" name="question" placeholder="Add suggested question" class="dash-input flex-1">
                <button type="submit" class="dash-btn-primary dash-btn-sm">Add</button>
            </form>
        </div>
    </div>
</div>
@endsection
