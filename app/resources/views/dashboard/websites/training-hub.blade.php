@extends('layouts.websites-workspace')

@section('title', 'Training — '.$website->name)

@section('ws-tab', 'training')
@section('ws-title', 'AI from URL')
@section('ws-subtitle', 'Generate Q and A pairs from any page with ChatGPT, then review and save to your library.')

@section('page-toolbar')
<a href="{{ route('websites.questions.index', $website) }}" class="dash-btn-secondary dash-btn-sm">{{ $qaCount }} saved Q&amp;A</a>
@endsection

@section('workspace')
<div class="ws-training" data-training-hub>
    <section class="ws-training-panel is-active" data-training-panel="ai" role="region" aria-label="AI from URL">
        <div class="ws-training-ai-hero dash-card">
            <div class="dash-card-body">
                <h2 class="ws-training-panel__title">Generate Q&amp;A from any page</h2>
                <p class="ws-training-panel__lead">ChatGPT reads the page content, suggests questions with trigger keywords, and you approve what gets saved to your Q&amp;A library.</p>
                <form method="POST" action="{{ route('websites.training.generate-qa', $website) }}" class="ws-training-ai-form">
                    @csrf
                    <div class="ws-training-ai-form__row">
                        <div class="dash-field flex-1 min-w-0">
                            <label class="dash-label" for="ai_url">Page URL</label>
                            <input type="url" id="ai_url" name="url" value="{{ old('url', $draft['url'] ?? $website->url) }}" required
                                class="dash-input w-full" placeholder="https://yoursite.com/page">
                        </div>
                        <div class="dash-field ws-training-ai-form__count">
                            <label class="dash-label" for="max_pairs">Pairs</label>
                            <input type="number" id="max_pairs" name="max_pairs" min="3" max="15" value="{{ old('max_pairs', 8) }}" class="dash-input w-full">
                        </div>
                        <button type="submit" class="dash-btn-primary ws-training-ai-form__submit" data-ai-generate>
                            <span class="ws-training-ai-form__submit-text">Generate with AI</span>
                            <span class="ws-training-ai-form__spinner" hidden aria-hidden="true"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($draft && !empty($draft['pairs']))
            <form method="POST" action="{{ route('websites.training.approve-qa', $website) }}" class="ws-training-review dash-card" id="qa-review-form">
                @csrf
                <div class="dash-card-header cf-split flex-wrap gap-2">
                    <div>
                        <h3 class="font-semibold">Review &amp; verify</h3>
                        <p class="text-sm dash-muted mt-1">From <a href="{{ $draft['url'] }}" target="_blank" rel="noopener">{{ $draft['title'] ?? $draft['url'] }}</a></p>
                    </div>
                    <div class="cf-cluster">
                        <button type="button" class="dash-btn-ghost dash-btn-sm" data-qa-select-all>Select all</button>
                        <form method="POST" action="{{ route('websites.training.discard-qa', $website) }}" class="inline" onsubmit="return confirm('Discard all suggestions?');">
                            @csrf
                            <button type="submit" class="dash-btn-ghost dash-btn-sm">Discard</button>
                        </form>
                    </div>
                </div>
                <div class="dash-card-body space-y-3">
                    @foreach($draft['pairs'] as $index => $pair)
                        <article class="ws-training-review-card" data-qa-review-card>
                            <label class="ws-training-review-card__check dash-checkbox-row">
                                <input type="checkbox" name="items[{{ $index }}][approve]" value="1" checked class="qa-approve-check">
                                <span>Save this pair</span>
                            </label>
                            <div class="ws-training-review-card__fields">
                                <div class="dash-field">
                                    <label class="dash-label">Question</label>
                                    <input type="text" name="items[{{ $index }}][question]" value="{{ $pair['question'] }}" required class="dash-input w-full">
                                </div>
                                <div class="dash-field">
                                    <label class="dash-label">Answer</label>
                                    <textarea name="items[{{ $index }}][answer]" rows="3" required class="dash-textarea w-full">{{ $pair['answer'] }}</textarea>
                                </div>
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div class="dash-field">
                                        <label class="dash-label">Trigger keywords</label>
                                        <input type="text" name="items[{{ $index }}][trigger_keywords]"
                                            value="{{ implode(', ', $pair['trigger_keywords'] ?? []) }}"
                                            class="dash-input w-full" placeholder="pricing, shipping, warranty">
                                    </div>
                                    <div class="dash-field">
                                        <label class="dash-label">Category</label>
                                        <input type="text" name="items[{{ $index }}][category]" value="From URL" class="dash-input w-full">
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                    <button type="submit" class="dash-btn-primary w-full sm:w-auto">Save approved to Q&amp;A library</button>
                </div>
            </form>
        @else
            <p class="ws-training-empty-hint">Enter a URL above and click <strong>Generate with AI</strong> to create draft Q&amp;A pairs.</p>
        @endif
    </section>
</div>

@push('scripts')
<script src="{{ asset('js/training-hub.js') }}" defer></script>
@endpush
@endsection
