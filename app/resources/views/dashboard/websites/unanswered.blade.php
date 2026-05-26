@extends('layouts.websites-workspace')

@section('title', 'Unanswered — '.$website->name)

@section('ws-tab', 'unanswered')
@section('ws-title', 'Unanswered questions')
@section('ws-subtitle', 'Add answers to retrain the bot instantly.')

@section('workspace')
    @if($questions->where('status', 'open')->count() > 0)
        <form method="POST" action="{{ route('websites.unanswered.bulk', $website) }}" id="unanswered-bulk-form" class="mt-6 dash-card p-4 hidden" data-bulk-bar>
            @csrf
            <input type="hidden" name="action" id="unanswered-bulk-action" value="dismiss">
            <p class="text-sm dash-muted mb-3"><span data-bulk-count>0</span> selected</p>
            <div class="flex flex-wrap gap-2 items-end">
                <button type="submit" class="dash-btn-ghost dash-btn-sm" data-bulk-submit="dismiss">Dismiss selected</button>
                <button type="button" class="dash-btn-primary dash-btn-sm" data-bulk-promote-toggle>Promote to Q&amp;A</button>
            </div>
            <div id="unanswered-bulk-promote" class="mt-4 space-y-3 hidden">
                <div id="unanswered-bulk-shared">
                    <textarea name="answer" rows="2" class="dash-textarea w-full text-sm max-w-xl" placeholder="Same answer for all selected questions…"></textarea>
                </div>
                <div id="unanswered-bulk-rows" class="space-y-3 hidden max-w-2xl"></div>
                <input type="text" name="category" class="dash-input w-full max-w-xs text-sm" placeholder="Category (optional, applies to all)">
                <button type="submit" class="dash-btn-primary dash-btn-sm" data-bulk-submit="promote">Add to Q&amp;A</button>
            </div>
        </form>
    @endif

    <div class="dash-table-wrap mt-8">
        <table class="dash-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th>Visitor asked</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>When</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($questions as $q)
                    <tr data-question-id="{{ $q->id }}" data-question-text="{{ e($q->visitor_message) }}">
                        <td>
                            @if($q->status === 'open')
                                <input type="checkbox" class="dash-checkbox" name="ids[]" value="{{ $q->id }}" form="unanswered-bulk-form" data-unanswered-select>
                            @endif
                        </td>
                        <td class="max-w-md">{{ Str::limit($q->visitor_message, 120) }}</td>
                        <td><span class="dash-badge">{{ $q->source }}</span></td>
                        <td>{{ ucfirst($q->status) }}</td>
                        <td class="text-sm dash-muted">{{ $q->created_at->diffForHumans() }}</td>
                        <td>
                            @if($q->status === 'open')
                                <form method="POST" action="{{ route('websites.unanswered.resolve', [$website, $q]) }}" class="space-y-2 min-w-[240px]">
                                    @csrf
                                    <textarea name="answer" rows="2" required class="dash-textarea w-full text-sm" placeholder="Write the correct answer…"></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="dash-btn-primary dash-btn-sm">Add to Q&amp;A</button>
                                        <button formaction="{{ route('websites.unanswered.dismiss', [$website, $q]) }}" formmethod="POST" class="dash-btn-ghost dash-btn-sm">Dismiss</button>
                                    </div>
                                </form>
                            @else
                                <span class="text-sm dash-muted">Resolved</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-8 dash-muted">No unanswered questions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $questions->links() }}</div>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('unanswered-bulk-form');
    if (!form) return;
    const bar = form;
    const countEl = form.querySelector('[data-bulk-count]');
    const actionInput = document.getElementById('unanswered-bulk-action');
    const promoteBox = document.getElementById('unanswered-bulk-promote');
    const sharedBox = document.getElementById('unanswered-bulk-shared');
    const rowsBox = document.getElementById('unanswered-bulk-rows');
    const checkboxes = () => [...document.querySelectorAll('[data-unanswered-select]:checked')];

    function syncBar() {
        const n = checkboxes().length;
        bar.classList.toggle('hidden', n === 0);
        if (countEl) countEl.textContent = String(n);
        if (promoteBox && !promoteBox.classList.contains('hidden')) {
            buildPromoteFields();
        }
    }

    function buildPromoteFields() {
        const selected = checkboxes();
        const multi = selected.length > 1;
        sharedBox?.classList.toggle('hidden', multi);
        rowsBox?.classList.toggle('hidden', !multi);
        if (!rowsBox) return;
        rowsBox.innerHTML = '';
        if (!multi) return;

        selected.forEach((cb) => {
            const row = cb.closest('tr');
            const id = cb.value;
            const text = row?.dataset.questionText || 'Question';
            const wrap = document.createElement('div');
            wrap.className = 'space-y-1';
            wrap.innerHTML = `
                <label class="text-xs font-medium text-slate-700">${text.replace(/</g, '&lt;').slice(0, 100)}</label>
                <textarea name="answers[${id}]" rows="2" required class="dash-textarea w-full text-sm" placeholder="Answer for this question…"></textarea>
            `;
            rowsBox.appendChild(wrap);
        });
    }

    document.querySelectorAll('[data-unanswered-select]').forEach((cb) => {
        cb.addEventListener('change', syncBar);
    });

    document.querySelector('[data-bulk-promote-toggle]')?.addEventListener('click', () => {
        promoteBox?.classList.toggle('hidden');
        if (promoteBox && !promoteBox.classList.contains('hidden')) {
            buildPromoteFields();
        }
    });

    form.querySelectorAll('[data-bulk-submit]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const action = btn.getAttribute('data-bulk-submit');
            if (actionInput) actionInput.value = action;
            if (action === 'promote') {
                const selected = checkboxes();
                if (selected.length > 1) {
                    const missing = [...rowsBox?.querySelectorAll('textarea') || []].some((ta) => !ta.value.trim());
                    if (missing) {
                        e.preventDefault();
                        rowsBox?.querySelector('textarea:not([value])')?.focus();
                    }
                } else {
                    const answer = sharedBox?.querySelector('[name="answer"]');
                    if (!answer?.value.trim()) {
                        e.preventDefault();
                        answer?.focus();
                    }
                }
            }
        });
    });
})();
</script>
@endpush
@endsection
