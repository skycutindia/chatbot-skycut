@extends('layouts.websites-workspace')

@section('title', 'Train — '.$website->name)

@section('ws-tab', 'training')
@section('ws-title', 'Knowledge training')
@section('ws-subtitle', 'Crawl pages, upload files, and import content into the knowledge base.')

@section('page-toolbar')
<a href="{{ route('websites.knowledge.index', $website) }}" class="dash-btn-secondary dash-btn-sm">Knowledge articles</a>
@endsection

@section('workspace')
    <div class="ws-page-grid ws-page-grid--2">
        <form method="POST" action="{{ route('websites.knowledge.crawl', $website) }}" class="dash-card">
            <div class="dash-card-body space-y-4">
                @csrf
                <h2 class="dash-form-section-title">Website crawler</h2>
                <p class="text-sm dash-muted">Indexes same-domain pages (max {{ config('chatbot.crawl.max_pages') }} pages, depth {{ config('chatbot.crawl.max_depth') }}).</p>
                <div class="dash-field">
                    <label class="dash-label" for="crawl_label">Label (optional)</label>
                    <input id="crawl_label" name="label" placeholder="Label (optional)" class="dash-input w-full">
                </div>
                <div class="dash-field">
                    <label class="dash-label" for="crawl_url">URL</label>
                    <input id="crawl_url" type="url" name="url" value="{{ $website->url }}" placeholder="https://example.com" required class="dash-input w-full">
                </div>
                <button type="submit" class="dash-btn-primary w-full">Start crawl (queued)</button>
            </div>
        </form>

        <form method="POST" action="{{ route('websites.knowledge.upload', $website) }}" enctype="multipart/form-data" class="dash-card">
            <div class="dash-card-body space-y-4">
                @csrf
                <h2 class="dash-form-section-title">File upload</h2>
                <p class="text-sm dash-muted">TXT, CSV, DOCX, or PDF — content is extracted and indexed as articles.</p>
                <div class="dash-field">
                    <label class="dash-label" for="upload_label">Label (optional)</label>
                    <input id="upload_label" name="label" placeholder="Label (optional)" class="dash-input w-full">
                </div>
                <div class="dash-field">
                    <label class="dash-label" for="upload_file">File</label>
                    <input id="upload_file" type="file" name="file" accept=".txt,.csv,.docx,.pdf" required class="dash-input w-full">
                </div>
                <button type="submit" class="dash-btn-primary w-full">Upload & index (queued)</button>
            </div>
        </form>
    </div>

    <div class="mt-10">
        <h2 class="dash-form-section-title">Training jobs</h2>
        <ul class="dash-list mt-4">
            @forelse($sources as $source)
                <li class="dash-list-item">
                    <div>
                        <p class="font-medium">{{ $source->label ?: ucfirst($source->type) }}</p>
                        <p class="text-xs dash-muted mt-1">
                            {{ $source->type === 'crawl' ? $source->source_url : $source->file_name }}
                            · {{ $source->items_indexed }} indexed
                        </p>
                        @if($source->error_message)
                            <p class="text-xs text-red-500 mt-1">{{ $source->error_message }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="dash-badge
                            @if($source->status === 'completed') dash-badge-success
                            @elseif($source->status === 'failed') dash-badge-danger
                            @elseif($source->status === 'processing') dash-badge-warning
                            @else dash-badge-muted @endif">{{ $source->status }}</span>
                        <form method="POST" action="{{ route('websites.knowledge.sources.destroy', [$website, $source]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="dash-btn-danger dash-btn-sm">Remove</button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="dash-empty">No training jobs yet.</li>
            @endforelse
        </ul>
        <div class="mt-4">{{ $sources->links() }}</div>
    </div>
@endsection
