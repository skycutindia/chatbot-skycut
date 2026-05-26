@extends('layouts.websites-workspace')

@section('title', 'Q&A — '.$website->name)

@section('ws-tab', 'questions')
@section('ws-title', 'Questions and answers')
@section('ws-subtitle', 'Train this site with exact answers visitors can search, click, and trigger via keywords.')

@php
    $totalCount = $website->qaPairs()->count();
    $activeCount = $website->qaPairs()->where('is_active', true)->count();
    $disabledCount = max(0, $totalCount - $activeCount);
    $hasFilters = request()->hasAny(['q', 'category', 'status']);
@endphp

@push('styles')
<style>
    .qa-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 22px; }

    .qa-hero { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
    .qa-hero__stat { background: var(--dash-surface); border: 1px solid var(--dash-border); border-radius: 14px; padding: 14px 16px; display: flex; flex-direction: column; gap: 4px; }
    .qa-hero__num { font-size: 22px; font-weight: 700; color: var(--dash-text); }
    .qa-hero__label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--dash-muted); font-weight: 600; }
    .qa-hero__stat--accent { background: linear-gradient(135deg, var(--dash-accent-muted, #eef2ff), var(--dash-surface)); border-color: var(--dash-accent, #2563eb); }

    .qa-bar { background: var(--dash-surface); border: 1px solid var(--dash-border); border-radius: 14px; padding: 12px 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .qa-bar__search { flex: 1; min-width: 240px; position: relative; }
    .qa-bar__search input { padding-left: 38px !important; }
    .qa-bar__search::before { content: '🔍'; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); opacity: 0.55; pointer-events: none; }
    .qa-chip-row { display: flex; flex-wrap: wrap; gap: 6px; }
    .qa-chip { font-size: 12px; padding: 6px 12px; border-radius: 999px; background: var(--dash-bg, #f1f5f9); color: var(--dash-text-muted); border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background 0.15s, color 0.15s, border-color 0.15s; }
    .qa-chip:hover { background: var(--dash-surface); border-color: var(--dash-border); }
    .qa-chip.is-active { background: var(--dash-accent, #2563eb); color: #fff; }

    .qa-bulk-bar { position: sticky; top: 8px; z-index: 5; background: var(--dash-text); color: #fff; border-radius: 12px; padding: 10px 16px; display: none; align-items: center; justify-content: space-between; gap: 12px; box-shadow: 0 10px 25px -10px rgba(0,0,0,0.4); }
    .qa-bulk-bar.is-visible { display: flex; }
    .qa-bulk-bar__count { font-weight: 700; }
    .qa-bulk-bar__btns { display: flex; gap: 6px; }
    .qa-bulk-bar__btn { background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; }
    .qa-bulk-bar__btn:hover { background: rgba(255,255,255,0.22); }
    .qa-bulk-bar__btn--danger:hover { background: #dc2626; border-color: #dc2626; }

    .qa-list { display: flex; flex-direction: column; gap: 10px; }
    .qa-item { background: var(--dash-surface); border: 1px solid var(--dash-border); border-radius: 14px; overflow: hidden; transition: border-color 0.15s, box-shadow 0.15s; }
    .qa-item:hover { border-color: var(--dash-accent, #2563eb); box-shadow: 0 4px 14px -6px rgba(15,23,42,0.16); }
    .qa-item.is-disabled { opacity: 0.65; background: repeating-linear-gradient(135deg, var(--dash-surface), var(--dash-surface) 6px, transparent 6px, transparent 12px); }
    .qa-item.is-open .qa-item__chev { transform: rotate(90deg); }

    .qa-item__row { display: grid; grid-template-columns: auto auto 1fr auto; gap: 14px; padding: 14px 16px; align-items: center; }
    .qa-item__chev { width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; color: var(--dash-muted); transition: transform 0.18s; cursor: pointer; background: none; border: none; padding: 0; }
    .qa-item__chev::before { content: '▶'; font-size: 10px; }
    .qa-item__check { width: 18px; height: 18px; cursor: pointer; }
    .qa-item__main { min-width: 0; cursor: pointer; }
    .qa-item__q { font-weight: 600; font-size: 14px; color: var(--dash-text); line-height: 1.4; word-break: break-word; }
    .qa-item__a { font-size: 12.5px; color: var(--dash-muted); margin-top: 4px; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .qa-item__meta { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .qa-item__tag { font-size: 11px; padding: 2px 8px; border-radius: 999px; background: var(--dash-bg, #f1f5f9); color: var(--dash-text-muted); font-weight: 500; }
    .qa-item__tag--category { background: rgba(99,102,241,0.12); color: #4f46e5; font-weight: 600; }
    .qa-item__tag--priority { background: rgba(245,158,11,0.14); color: #b45309; font-weight: 600; }
    .qa-item__tag--keyword { background: rgba(20,184,166,0.13); color: #0f766e; }
    .qa-item__quick { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

    .qa-item__edit { padding: 0 16px 18px; border-top: 1px solid var(--dash-border); display: none; background: var(--dash-bg, #f8fafc); }
    .qa-item.is-open .qa-item__edit { display: block; }
    .qa-edit-grid { padding-top: 14px; display: grid; gap: 12px; }
    .qa-edit-grid label { display: block; font-size: 12px; font-weight: 600; color: var(--dash-text); margin-bottom: 4px; }
    .qa-edit-grid .row-3 { display: grid; grid-template-columns: 1fr 1fr 120px; gap: 12px; }
    @media (max-width: 720px) { .qa-edit-grid .row-3 { grid-template-columns: 1fr; } }
    .qa-edit__footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding-top: 8px; border-top: 1px dashed var(--dash-border); }

    .qa-empty { padding: 60px 32px; background: var(--dash-surface); border: 2px dashed var(--dash-border); border-radius: 14px; text-align: center; color: var(--dash-muted); }
    .qa-empty h3 { color: var(--dash-text); margin: 0 0 8px; font-size: 16px; }

    .qa-modal { position: fixed; inset: 0; background: rgba(15,23,42,0.5); display: none; align-items: stretch; justify-content: flex-end; z-index: 1000; }
    .qa-modal.is-open { display: flex; animation: qa-fade 0.15s ease; }
    .qa-modal__panel { background: var(--dash-surface); width: min(540px, 100%); max-height: 100vh; overflow-y: auto; box-shadow: -10px 0 30px -10px rgba(0,0,0,0.25); animation: qa-slide 0.22s ease; }
    @keyframes qa-fade { from { opacity: 0; } to { opacity: 1; } }
    @keyframes qa-slide { from { transform: translateX(40px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    .qa-modal__header { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--dash-border); position: sticky; top: 0; background: var(--dash-surface); z-index: 2; }
    .qa-modal__title { font-size: 16px; font-weight: 700; }
    .qa-modal__close { background: none; border: none; cursor: pointer; font-size: 22px; line-height: 1; color: var(--dash-muted); padding: 4px 8px; border-radius: 6px; }
    .qa-modal__close:hover { background: var(--dash-bg); color: var(--dash-text); }
    .qa-modal__body { padding: 20px 22px 22px; display: grid; gap: 14px; }

    .qa-tabs-bar { display: flex; gap: 8px; padding: 4px; background: var(--dash-bg, #f1f5f9); border-radius: 10px; }
    .qa-tabs-bar button { flex: 1; background: transparent; border: none; padding: 8px 10px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer; color: var(--dash-text-muted); }
    .qa-tabs-bar button.is-active { background: var(--dash-surface); color: var(--dash-text); box-shadow: 0 1px 3px rgba(0,0,0,0.08); }

    .qa-import-drop { padding: 24px; border: 2px dashed var(--dash-border); border-radius: 12px; text-align: center; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
    .qa-import-drop:hover { border-color: var(--dash-accent, #2563eb); background: rgba(99,102,241,0.04); }
    .qa-import-drop p { margin: 6px 0; font-size: 13px; }
    .qa-import-drop input { display: none; }

    .qa-switch { position: relative; width: 36px; height: 20px; border-radius: 999px; background: #e2e8f0; cursor: pointer; transition: background 0.18s; border: none; padding: 0; }
    .qa-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.18); transition: transform 0.18s; }
    .qa-switch.is-on { background: #10b981; }
    .qa-switch.is-on::after { transform: translateX(16px); }

    .qa-icon-btn { width: 28px; height: 28px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: var(--dash-bg, #f8fafc); border: 1px solid var(--dash-border); color: var(--dash-text-muted); cursor: pointer; transition: background 0.12s, color 0.12s; padding: 0; }
    .qa-icon-btn:hover { background: var(--dash-accent, #2563eb); color: #fff; border-color: transparent; }
    .qa-icon-btn[data-danger="1"]:hover { background: #dc2626; }
</style>
@endpush

@section('workspace')
<div class="qa-layout">
    <div class="qa-hero">
        <div class="qa-hero__stat qa-hero__stat--accent">
            <span class="qa-hero__num">{{ $totalCount }}</span>
            <span class="qa-hero__label">Total Q&A</span>
        </div>
        <div class="qa-hero__stat">
            <span class="qa-hero__num">{{ $activeCount }}</span>
            <span class="qa-hero__label">Active</span>
        </div>
        <div class="qa-hero__stat">
            <span class="qa-hero__num">{{ $disabledCount }}</span>
            <span class="qa-hero__label">Disabled</span>
        </div>
        <div class="qa-hero__stat">
            <span class="qa-hero__num">{{ $categories->count() }}</span>
            <span class="qa-hero__label">Categories</span>
        </div>
    </div>

    <form method="GET" action="{{ route('websites.questions.index', $website) }}" class="qa-bar" id="qa-filter-form">
        <div class="qa-bar__search">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search questions, answers, keywords…" class="dash-input w-full">
        </div>
        @if($categories->isNotEmpty())
            <select name="category" class="dash-select" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        @endif
        @php
            $baseQueryParams = array_filter(['q' => request('q'), 'category' => request('category')]);
        @endphp
        <div class="qa-chip-row">
            <a href="{{ route('websites.questions.index', array_merge(['website' => $website], $baseQueryParams)) }}" class="qa-chip {{ ! request('status') ? 'is-active' : '' }}">All</a>
            <a href="{{ route('websites.questions.index', array_merge(['website' => $website], $baseQueryParams, ['status' => 'enabled'])) }}" class="qa-chip {{ request('status') === 'enabled' ? 'is-active' : '' }}">Active</a>
            <a href="{{ route('websites.questions.index', array_merge(['website' => $website], $baseQueryParams, ['status' => 'disabled'])) }}" class="qa-chip {{ request('status') === 'disabled' ? 'is-active' : '' }}">Disabled</a>
        </div>
        <div style="flex: 1; min-width: 12px;"></div>
        @if($hasFilters)
            <a href="{{ route('websites.questions.index', $website) }}" class="dash-btn-ghost dash-btn-sm">Clear</a>
        @endif
        <button type="submit" class="dash-btn-secondary dash-btn-sm" style="display:none">Search</button>
        <button type="button" class="dash-btn-ghost dash-btn-sm" data-qa-open="import">📥 Import CSV</button>
        <button type="button" class="dash-btn-primary dash-btn-sm" data-qa-open="add">+ Add Q&A</button>
    </form>

    {{-- Bulk actions floater --}}
    <div class="qa-bulk-bar" id="qa-bulk-bar">
        <span class="qa-bulk-bar__count"><span id="qa-selected-count">0</span> selected</span>
        <div class="qa-bulk-bar__btns">
            <button type="button" class="qa-bulk-bar__btn" id="qa-bulk-clear">Clear</button>
            <button type="button" class="qa-bulk-bar__btn qa-bulk-bar__btn--danger" id="qa-bulk-delete">Delete selected</button>
        </div>
    </div>
    <form method="POST" action="{{ route('websites.questions.bulk-delete', $website) }}" id="qa-bulk-form" style="display:none">
        @csrf
        <div id="qa-bulk-inputs"></div>
    </form>

    @if($questions->isEmpty())
        <div class="qa-empty">
            <h3>{{ $hasFilters ? 'No matches' : 'No Q&A pairs yet' }}</h3>
            <p>
                @if($hasFilters)
                    Try changing your search or <a href="{{ route('websites.questions.index', $website) }}" style="color: var(--dash-accent, #2563eb); font-weight: 600;">clear filters</a>.
                @else
                    Add your first question and answer, or import a CSV file to bulk-load them.
                @endif
            </p>
            @unless($hasFilters)
                <div class="flex gap-2 justify-center mt-4">
                    <button type="button" class="dash-btn-primary" data-qa-open="add">+ Add Q&A</button>
                    <button type="button" class="dash-btn-ghost" data-qa-open="import">Import CSV</button>
                </div>
            @endunless
        </div>
    @else
        <div class="qa-list">
            @foreach($questions as $qa)
                <article class="qa-item {{ $qa->is_active ? '' : 'is-disabled' }}" data-id="{{ $qa->id }}">
                    <div class="qa-item__row">
                        <input type="checkbox" class="qa-item__check qa-bulk-cb" value="{{ $qa->id }}" aria-label="Select">
                        <button type="button" class="qa-item__chev" aria-label="Toggle edit"></button>
                        <div class="qa-item__main" role="button" tabindex="0">
                            <div class="qa-item__q">{{ $qa->question }}</div>
                            <div class="qa-item__a">{{ Str::limit(strip_tags($qa->answer), 200) }}</div>
                            <div class="qa-item__meta">
                                @if(!$qa->is_active)
                                    <span class="qa-item__tag">Disabled</span>
                                @endif
                                @if($qa->category)
                                    <span class="qa-item__tag qa-item__tag--category">{{ $qa->category }}</span>
                                @endif
                                @if($qa->priority)
                                    <span class="qa-item__tag qa-item__tag--priority">Priority {{ $qa->priority }}</span>
                                @endif
                                @if($qa->alternate_answers)
                                    <span class="qa-item__tag">+{{ count($qa->alternate_answers) }} alt</span>
                                @endif
                                @foreach(($qa->trigger_keywords ?? []) as $kw)
                                    <span class="qa-item__tag qa-item__tag--keyword">#{{ $kw }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="qa-item__quick">
                            <button type="button" class="qa-switch {{ $qa->is_active ? 'is-on' : '' }}" data-qa-toggle="{{ $qa->id }}" title="Toggle active" aria-label="Toggle"></button>
                            <form method="POST" action="{{ route('websites.questions.clone', [$website, $qa]) }}" style="display:inline">
                                @csrf
                                <button type="submit" class="qa-icon-btn" title="Duplicate">⎘</button>
                            </form>
                            <form method="POST" action="{{ route('websites.questions.destroy', [$website, $qa]) }}" style="display:inline" onsubmit="return confirm('Delete this question?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="qa-icon-btn" data-danger="1" title="Delete">🗑</button>
                            </form>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('websites.questions.update', [$website, $qa]) }}" class="qa-item__edit">
                        @csrf @method('PUT')
                        <div class="qa-edit-grid">
                            <div>
                                <label>Question</label>
                                <input name="question" value="{{ $qa->question }}" required maxlength="500" class="dash-input w-full">
                            </div>
                            <div>
                                <label>Primary answer</label>
                                <textarea name="answer" rows="3" required maxlength="5000" class="dash-textarea w-full">{{ $qa->answer }}</textarea>
                            </div>
                            <div>
                                <label>Alternate answers <span class="dash-muted">(one per line — bot will pick at random)</span></label>
                                <textarea name="alternate_answers_text" rows="2" class="dash-textarea w-full">{{ $qa->alternate_answers ? implode("\n", $qa->alternate_answers) : '' }}</textarea>
                            </div>
                            <div>
                                <label>Trigger keywords <span class="dash-muted">(comma separated)</span></label>
                                <input name="trigger_keywords" value="{{ $qa->trigger_keywords ? implode(', ', $qa->trigger_keywords) : '' }}" class="dash-input w-full">
                            </div>
                            <div class="row-3">
                                <div>
                                    <label>Category</label>
                                    <input name="category" value="{{ $qa->category }}" maxlength="120" class="dash-input w-full" list="qa-cats">
                                </div>
                                <div>
                                    <label>Tags</label>
                                    <input name="tags" value="{{ $qa->tags ? implode(', ', $qa->tags) : '' }}" maxlength="500" class="dash-input w-full" placeholder="comma separated">
                                </div>
                                <div>
                                    <label>Priority</label>
                                    <input name="priority" type="number" min="0" max="100" value="{{ $qa->priority }}" class="dash-input w-full">
                                </div>
                            </div>
                            <div class="qa-edit__footer">
                                <label class="dash-checkbox-row">
                                    <input type="checkbox" name="is_active" value="1" @checked($qa->is_active)>
                                    <span>Enabled</span>
                                </label>
                                <button type="submit" class="dash-btn-primary dash-btn-sm">Save changes</button>
                            </div>
                        </div>
                    </form>
                </article>
            @endforeach
        </div>
        <div style="margin-top: 18px;">{{ $questions->links() }}</div>
    @endif
</div>

<datalist id="qa-cats">
    @foreach($categories as $cat)
        <option value="{{ $cat }}">
    @endforeach
</datalist>

{{-- Add modal --}}
<div class="qa-modal" id="qa-modal-add" role="dialog" aria-modal="true">
    <div class="qa-modal__panel">
        <header class="qa-modal__header">
            <span class="qa-modal__title">Add new Q&A</span>
            <button type="button" class="qa-modal__close" data-qa-close="">×</button>
        </header>
        <form method="POST" action="{{ route('websites.questions.store', $website) }}" class="qa-modal__body">
            @csrf
            <div class="dash-field">
                <label class="dash-label">Question <span class="text-red-500">*</span></label>
                <input name="question" required maxlength="500" class="dash-input w-full" placeholder="What is your refund policy?">
            </div>
            <div class="dash-field">
                <label class="dash-label">Primary answer <span class="text-red-500">*</span></label>
                <textarea name="answer" rows="4" required maxlength="5000" class="dash-textarea w-full" placeholder="The friendly, direct answer your bot will use."></textarea>
            </div>
            <div class="dash-field">
                <label class="dash-label">Alternate answers <span class="dash-muted text-xs">(optional, one per line)</span></label>
                <textarea name="alternate_answers_text" rows="2" class="dash-textarea w-full" placeholder="Each line is a variant; the bot picks one at random."></textarea>
            </div>
            <div class="dash-field">
                <label class="dash-label">Trigger keywords <span class="dash-muted text-xs">(comma separated)</span></label>
                <input name="trigger_keywords" class="dash-input w-full" placeholder="refund, return, money back">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="dash-field">
                    <label class="dash-label">Category</label>
                    <input name="category" maxlength="120" class="dash-input w-full" list="qa-cats" placeholder="e.g. Billing">
                </div>
                <div class="dash-field">
                    <label class="dash-label">Tags</label>
                    <input name="tags" maxlength="500" class="dash-input w-full" placeholder="vip, fast">
                </div>
                <div class="dash-field">
                    <label class="dash-label">Priority</label>
                    <input name="priority" type="number" min="0" max="100" value="0" class="dash-input w-full">
                </div>
            </div>
            <label class="dash-checkbox-row">
                <input type="checkbox" name="is_active" value="1" checked>
                <span>Show this answer to visitors</span>
            </label>
            <div class="flex justify-end gap-2 pt-2 border-t border-[var(--dash-border)]">
                <button type="button" class="dash-btn-ghost" data-qa-close="">Cancel</button>
                <button type="submit" class="dash-btn-primary">Add Q&A</button>
            </div>
        </form>
    </div>
</div>

{{-- Import modal --}}
<div class="qa-modal" id="qa-modal-import" role="dialog" aria-modal="true">
    <div class="qa-modal__panel">
        <header class="qa-modal__header">
            <span class="qa-modal__title">Import CSV</span>
            <button type="button" class="qa-modal__close" data-qa-close="">×</button>
        </header>
        <form method="POST" action="{{ route('websites.questions.import', $website) }}" enctype="multipart/form-data" class="qa-modal__body">
            @csrf
            <p class="text-sm dash-muted">
                Upload a CSV with the columns:
                <code class="text-xs">question, answer, keywords, category, tags, priority</code>.
                Only the first two are required.
            </p>
            <label class="qa-import-drop" id="qa-import-drop">
                <p style="font-size: 28px; margin: 0;">📎</p>
                <p><strong id="qa-import-filename">Click to choose a CSV file</strong></p>
                <p class="dash-muted" style="font-size: 11px;">or drop it here · max 5 MB</p>
                <input type="file" name="file" accept=".csv,.txt" required id="qa-import-input">
            </label>
            <div class="flex justify-end gap-2 pt-2 border-t border-[var(--dash-border)]">
                <button type="button" class="dash-btn-ghost" data-qa-close="">Cancel</button>
                <button type="submit" class="dash-btn-primary">Import CSV</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const csrf = @json(csrf_token());

    // Open / close modal
    document.querySelectorAll('[data-qa-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.qaOpen;
            const modal = document.getElementById('qa-modal-' + key);
            if (modal) {
                modal.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }
        });
    });
    document.querySelectorAll('[data-qa-close]').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.qa-modal.is-open').forEach((m) => m.classList.remove('is-open'));
            document.body.style.overflow = '';
        });
    });
    document.querySelectorAll('.qa-modal').forEach((modal) => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('is-open');
                document.body.style.overflow = '';
            }
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.qa-modal.is-open').forEach((m) => m.classList.remove('is-open'));
            document.body.style.overflow = '';
        }
    });

    // Expand/collapse on chev or main click
    document.querySelectorAll('.qa-item').forEach((item) => {
        const chev = item.querySelector('.qa-item__chev');
        const main = item.querySelector('.qa-item__main');
        const toggle = () => item.classList.toggle('is-open');
        chev?.addEventListener('click', toggle);
        main?.addEventListener('click', toggle);
        main?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); toggle(); } });
    });

    // Bulk select bar
    const bulkBar = document.getElementById('qa-bulk-bar');
    const bulkForm = document.getElementById('qa-bulk-form');
    const bulkInputs = document.getElementById('qa-bulk-inputs');
    const countEl = document.getElementById('qa-selected-count');
    const cbs = document.querySelectorAll('.qa-bulk-cb');
    function refreshBulk() {
        const selected = Array.from(cbs).filter((c) => c.checked).map((c) => c.value);
        countEl.textContent = selected.length;
        bulkBar.classList.toggle('is-visible', selected.length > 0);
        bulkInputs.innerHTML = selected.map((id) => `<input type="hidden" name="ids[]" value="${id}">`).join('');
    }
    cbs.forEach((cb) => cb.addEventListener('change', refreshBulk));
    document.getElementById('qa-bulk-clear')?.addEventListener('click', () => {
        cbs.forEach((c) => { c.checked = false; });
        refreshBulk();
    });
    document.getElementById('qa-bulk-delete')?.addEventListener('click', () => {
        if (confirm('Delete the selected questions? This cannot be undone.')) {
            bulkForm.submit();
        }
    });

    // Toggle active (sync via PUT with full edit form would re-render; use lightweight approach: submit edit form with only is_active)
    document.querySelectorAll('[data-qa-toggle]').forEach((sw) => {
        sw.addEventListener('click', async () => {
            const item = sw.closest('.qa-item');
            const form = item.querySelector('.qa-item__edit');
            if (!form) return;
            const cb = form.querySelector('input[name="is_active"]');
            cb.checked = !cb.checked;
            sw.classList.toggle('is-on', cb.checked);
            item.classList.toggle('is-disabled', !cb.checked);
            const fd = new FormData(form);
            if (!cb.checked) fd.delete('is_active');
            else fd.set('is_active', '1');
            fd.set('_token', csrf);
            sw.disabled = true;
            try {
                await fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            } finally {
                sw.disabled = false;
            }
        });
    });

    // Live search (debounced auto-submit)
    const filterForm = document.getElementById('qa-filter-form');
    const search = filterForm?.querySelector('input[name="q"]');
    let st;
    search?.addEventListener('input', () => {
        clearTimeout(st);
        st = setTimeout(() => filterForm.submit(), 400);
    });

    // Import drag/drop
    const drop = document.getElementById('qa-import-drop');
    const input = document.getElementById('qa-import-input');
    const fname = document.getElementById('qa-import-filename');
    input?.addEventListener('change', () => {
        if (input.files[0]) fname.textContent = input.files[0].name;
    });
    drop?.addEventListener('dragover', (e) => { e.preventDefault(); drop.style.borderColor = 'var(--dash-accent, #2563eb)'; });
    drop?.addEventListener('dragleave', () => { drop.style.borderColor = ''; });
    drop?.addEventListener('drop', (e) => {
        e.preventDefault();
        drop.style.borderColor = '';
        if (e.dataTransfer.files[0]) {
            input.files = e.dataTransfer.files;
            fname.textContent = e.dataTransfer.files[0].name;
        }
    });
})();
</script>
@endpush
@endsection
