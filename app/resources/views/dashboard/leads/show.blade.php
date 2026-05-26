@extends('layouts.app')

@section('title', 'Lead #'.$lead->id)

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Lead CRM</p>
        <h1 class="dash-page-title">{{ $lead->name ?: 'Lead #'.$lead->id }}</h1>
        <p class="dash-page-sub">{{ $lead->email }} · {{ $lead->phone }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-medium">
    <a href="{{ route('leads.index') }}" class="dash-back-link">← Back to leads</a>

    <div class="grid md:grid-cols-2 gap-6 mt-8">
        <div class="dash-card">
            <div class="dash-card-header">
                <h2 class="font-semibold">Details</h2>
            </div>
            <div class="dash-card-body">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="dash-muted">Company</dt><dd>{{ $lead->company ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="dash-muted">Website</dt><dd>{{ $lead->website?->name }}</dd></div>
                    <div class="flex justify-between"><dt class="dash-muted">Source</dt><dd class="truncate max-w-xs">{{ $lead->source_url ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="dash-muted">IP</dt><dd>{{ $lead->ip_address ?: '—' }}</dd></div>
                </dl>
                <form method="POST" action="{{ route('leads.update', $lead) }}" class="mt-6 space-y-3">
                    @csrf @method('PATCH')
                    <div class="dash-field">
                        <label class="dash-label" for="status">Pipeline status</label>
                        <select id="status" name="status" class="dash-select w-full">
                            @foreach(\App\Enums\LeadStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected($lead->status === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="dash-btn-primary dash-btn-sm">Update</button>
                </form>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-card-header">
                <h2 class="font-semibold">Notes</h2>
            </div>
            <div class="dash-card-body">
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($lead->notes as $note)
                        <div class="text-sm border-l-2 border-[var(--brand)] pl-3">
                            <p>{{ $note->body }}</p>
                            <p class="text-xs dash-muted mt-1">{{ $note->user?->name }} · {{ $note->created_at?->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('leads.notes.store', $lead) }}" class="mt-4 flex gap-2">
                    @csrf
                    <input type="text" name="body" placeholder="Add note..." class="dash-input flex-1" required>
                    <button type="submit" class="dash-btn-primary dash-btn-sm">Add</button>
                </form>
            </div>
        </div>
    </div>

    @if($lead->chat_transcript)
        <div class="dash-card mt-8">
            <div class="dash-card-header">
                <h2 class="font-semibold">Chat transcript</h2>
            </div>
            <div class="dash-card-body">
                <pre class="text-xs dash-muted whitespace-pre-wrap max-h-96 overflow-y-auto">{{ $lead->chat_transcript }}</pre>
            </div>
        </div>
    @endif
</div>
@endsection
