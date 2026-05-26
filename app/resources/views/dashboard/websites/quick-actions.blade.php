@extends('layouts.websites-workspace')

@section('title', 'Quick actions — '.$website->name)

@section('ws-tab', 'quick-actions')
@section('ws-title', 'Quick action buttons')
@section('ws-subtitle', 'Design tappable shortcuts shown above the chat input. Send a custom answer, open WhatsApp, link to anything.')

@php
    $totalActive = $actions->where('is_active', true)->count();
    $widgetPrimary = optional($website->configuration)->primary_color ?: '#2563eb';
@endphp

@push('styles')
<style>
    .qa-page { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 24px; align-items: start; }
    @media (max-width: 1100px) { .qa-page { grid-template-columns: 1fr; } }

    .qa-toolbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 18px; }
    .qa-toolbar__left { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .qa-stat { background: var(--dash-surface); border: 1px solid var(--dash-border); border-radius: 999px; padding: 6px 14px; font-size: 12px; font-weight: 600; color: var(--dash-text-muted); }
    .qa-stat strong { color: var(--dash-text); font-weight: 700; margin-right: 4px; }

    .qa-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }

    .qa-card { position: relative; background: var(--dash-surface); border: 1px solid var(--dash-border); border-radius: 14px; padding: 16px; cursor: grab; transition: box-shadow 0.18s, transform 0.18s, border-color 0.18s; }
    .qa-card:hover { box-shadow: 0 6px 18px -8px rgba(15, 23, 42, 0.18); border-color: var(--dash-accent, #2563eb); }
    .qa-card.is-dragging { opacity: 0.55; transform: scale(0.98); cursor: grabbing; }
    .qa-card.is-inactive { opacity: 0.62; background: repeating-linear-gradient(135deg, var(--dash-surface), var(--dash-surface) 6px, transparent 6px, transparent 12px); }

    .qa-card__top { display: flex; align-items: flex-start; gap: 12px; }
    .qa-card__chip { width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; color: #fff; flex-shrink: 0; box-shadow: inset 0 -2px 0 rgba(0,0,0,0.12); font-weight: 700; line-height: 1; }
    .qa-card__title { font-weight: 700; font-size: 14px; color: var(--dash-text); line-height: 1.3; word-break: break-word; }
    .qa-card__desc { font-size: 12px; color: var(--dash-text-muted); margin-top: 2px; line-height: 1.4; }
    .qa-card__meta { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
    .qa-type-badge { font-size: 11px; padding: 3px 8px; border-radius: 999px; background: rgba(99,102,241,0.1); color: #4f46e5; font-weight: 600; }
    .qa-type-badge[data-t="answer"] { background: rgba(16,185,129,0.12); color: #047857; }
    .qa-type-badge[data-t="url"] { background: rgba(59,130,246,0.12); color: #1d4ed8; }
    .qa-type-badge[data-t="whatsapp"] { background: rgba(34,197,94,0.14); color: #15803d; }
    .qa-type-badge[data-t="email"] { background: rgba(14,165,233,0.14); color: #0369a1; }
    .qa-type-badge[data-t="phone"] { background: rgba(168,85,247,0.14); color: #7e22ce; }
    .qa-type-badge[data-t="message"] { background: rgba(245,158,11,0.14); color: #b45309; }
    .qa-type-badge[data-t="internal"] { background: rgba(100,116,139,0.16); color: #475569; }
    .qa-card__sub { font-size: 11px; color: var(--dash-text-muted); margin-top: 8px; word-break: break-all; }

    .qa-card__actions { position: absolute; top: 10px; right: 10px; display: flex; gap: 4px; opacity: 0; transition: opacity 0.15s; }
    .qa-card:hover .qa-card__actions, .qa-card:focus-within .qa-card__actions { opacity: 1; }
    .qa-icon-btn { width: 28px; height: 28px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: var(--dash-bg, #f8fafc); border: 1px solid var(--dash-border); color: var(--dash-text-muted); cursor: pointer; transition: background 0.12s, color 0.12s; }
    .qa-icon-btn:hover { background: var(--dash-accent, #2563eb); color: #fff; border-color: transparent; }
    .qa-icon-btn[data-danger="1"]:hover { background: #dc2626; }

    .qa-switch { position: relative; width: 36px; height: 20px; border-radius: 999px; background: #e2e8f0; cursor: pointer; transition: background 0.18s; border: none; padding: 0; }
    .qa-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.18); transition: transform 0.18s; }
    .qa-switch.is-on { background: #10b981; }
    .qa-switch.is-on::after { transform: translateX(16px); }

    .qa-empty { padding: 56px 32px; border: 2px dashed var(--dash-border); border-radius: 16px; text-align: center; color: var(--dash-text-muted); }
    .qa-empty h3 { color: var(--dash-text); margin: 0 0 8px; font-size: 16px; }

    /* Live preview */
    .qa-preview { position: sticky; top: 24px; background: var(--dash-surface); border: 1px solid var(--dash-border); border-radius: 18px; overflow: hidden; box-shadow: 0 12px 30px -16px rgba(15,23,42,0.25); }
    .qa-preview__header { padding: 12px 16px; background: {{ $widgetPrimary }}; color: #fff; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .qa-preview__header::before { content: '●'; opacity: 0.6; }
    .qa-preview__body { padding: 14px; background: linear-gradient(180deg, rgba(0,0,0,0.02), transparent 80px); min-height: 220px; }
    .qa-preview__bubble { background: var(--dash-bg, #f8fafc); border: 1px solid var(--dash-border); border-radius: 14px 14px 14px 4px; padding: 10px 12px; font-size: 13px; max-width: 80%; color: var(--dash-text); margin-bottom: 14px; }
    .qa-preview__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .qa-preview__btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 10px; color: #fff; font-size: 12px; font-weight: 600; line-height: 1.3; box-shadow: 0 1px 0 rgba(0,0,0,0.06); text-align: left; }
    .qa-preview__btn-icon { font-size: 16px; line-height: 1; flex-shrink: 0; }
    .qa-preview__btn-body { display: flex; flex-direction: column; min-width: 0; flex: 1; }
    .qa-preview__btn-label { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .qa-preview__btn-desc { font-size: 10.5px; opacity: 0.85; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .qa-preview__empty { color: var(--dash-text-muted); font-size: 12px; padding: 30px 10px; text-align: center; }
    .qa-preview__footer { padding: 8px 14px 14px; border-top: 1px solid var(--dash-border); font-size: 11px; color: var(--dash-text-muted); display: flex; justify-content: space-between; }

    /* Drawer */
    .qa-drawer { position: fixed; inset: 0; background: rgba(15,23,42,0.5); display: none; align-items: stretch; justify-content: flex-end; z-index: 1000; }
    .qa-drawer.is-open { display: flex; animation: qa-fade 0.15s ease; }
    .qa-drawer__panel { background: var(--dash-surface); width: min(520px, 100%); max-height: 100vh; overflow-y: auto; box-shadow: -10px 0 30px -10px rgba(0,0,0,0.25); animation: qa-slide 0.22s ease; }
    @keyframes qa-fade { from { opacity: 0; } to { opacity: 1; } }
    @keyframes qa-slide { from { transform: translateX(40px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    .qa-drawer__header { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--dash-border); position: sticky; top: 0; background: var(--dash-surface); z-index: 2; }
    .qa-drawer__title { font-size: 16px; font-weight: 700; }
    .qa-drawer__close { background: none; border: none; cursor: pointer; font-size: 22px; line-height: 1; color: var(--dash-text-muted); padding: 4px 8px; border-radius: 6px; }
    .qa-drawer__close:hover { background: var(--dash-bg); color: var(--dash-text); }
    .qa-drawer__body { padding: 20px 22px 22px; display: grid; gap: 16px; }

    .qa-tabs { display: flex; gap: 6px; background: var(--dash-bg, #f1f5f9); padding: 4px; border-radius: 10px; }
    .qa-tab { flex: 1; background: transparent; border: none; padding: 8px 10px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer; color: var(--dash-text-muted); transition: background 0.15s, color 0.15s; }
    .qa-tab.is-active { background: var(--dash-surface); color: var(--dash-text); box-shadow: 0 1px 3px rgba(0,0,0,0.08); }

    .qa-color-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; }
    .qa-color-swatch { width: 100%; aspect-ratio: 1; border-radius: 10px; border: 2px solid transparent; cursor: pointer; position: relative; }
    .qa-color-swatch.is-active { border-color: var(--dash-text); box-shadow: 0 0 0 2px var(--dash-surface), 0 0 0 3px var(--dash-text); }
    .qa-color-swatch.is-active::after { content: '✓'; position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 16px; text-shadow: 0 1px 2px rgba(0,0,0,0.3); }

    .qa-icon-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 6px; }
    .qa-icon-btn-pick { aspect-ratio: 1; border-radius: 8px; border: 1px solid var(--dash-border); background: var(--dash-bg); cursor: pointer; font-size: 18px; transition: background 0.12s, border-color 0.12s; }
    .qa-icon-btn-pick:hover { background: var(--dash-surface); border-color: var(--dash-text-muted); }
    .qa-icon-btn-pick.is-active { background: var(--dash-text); color: #fff; border-color: var(--dash-text); }

    .qa-conditional { padding: 12px; background: var(--dash-bg, #f8fafc); border-radius: 10px; border: 1px dashed var(--dash-border); font-size: 12px; }
    .qa-conditional[hidden] { display: none; }

    .qa-mini-preview { padding: 14px; border-radius: 12px; background: var(--dash-bg, #f8fafc); display: flex; justify-content: center; }
</style>
@endpush

@section('workspace')
<div class="qa-page">
    <div>
        <div class="qa-toolbar">
            <div class="qa-toolbar__left">
                <span class="qa-stat"><strong>{{ $actions->count() }}</strong> total</span>
                <span class="qa-stat"><strong>{{ $totalActive }}</strong> active</span>
                <span class="qa-stat" id="qa-drag-hint">Drag cards to reorder</span>
            </div>
            <button type="button" class="dash-btn-primary" data-qa-open="">+ New quick action</button>
        </div>

        @if($actions->isEmpty())
            <div class="qa-empty">
                <h3>No quick actions yet</h3>
                <p>Quick actions are big tappable buttons that appear above the chat input. Use them to send a canned answer, link to WhatsApp, or open a quote form.</p>
                <button type="button" class="dash-btn-primary mt-4" data-qa-open="">Create your first action</button>
            </div>
        @else
            <div class="qa-grid" id="qa-grid">
                @foreach($actions as $action)
                    @php
                        $color = $action->color ?: $widgetPrimary;
                        $icon = $action->icon ?: $action->initial;
                        $payload = [
                            'id' => $action->id,
                            'label' => $action->label,
                            'description' => $action->description,
                            'icon' => $action->icon,
                            'color' => $action->color,
                            'action_type' => $action->action_type,
                            'action_value' => $action->action_value,
                            'custom_answer' => $action->custom_answer,
                            'is_active' => (bool) $action->is_active,
                        ];
                    @endphp
                    <article class="qa-card {{ $action->is_active ? '' : 'is-inactive' }}"
                             data-id="{{ $action->id }}"
                             data-payload="{{ json_encode($payload) }}">
                        <div class="qa-card__actions">
                            <button type="button" class="qa-switch {{ $action->is_active ? 'is-on' : '' }}"
                                    data-qa-toggle="{{ $action->id }}"
                                    title="{{ $action->is_active ? 'Disable' : 'Enable' }}"
                                    aria-label="Toggle active"></button>
                            <button type="button" class="qa-icon-btn" data-qa-edit="{{ $action->id }}" title="Edit">✎</button>
                            <form method="POST" action="{{ route('websites.quick-actions.duplicate', [$website, $action]) }}" style="display:inline">
                                @csrf
                                <button type="submit" class="qa-icon-btn" title="Duplicate">⎘</button>
                            </form>
                            <form method="POST" action="{{ route('websites.quick-actions.destroy', [$website, $action]) }}" style="display:inline" onsubmit="return confirm('Remove “{{ $action->label }}”?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="qa-icon-btn" data-danger="1" title="Delete">🗑</button>
                            </form>
                        </div>
                        <div class="qa-card__top">
                            <span class="qa-card__chip" style="background: {{ $color }}">{{ $icon }}</span>
                            <div>
                                <div class="qa-card__title">{{ $action->label }}</div>
                                @if($action->description)
                                    <div class="qa-card__desc">{{ $action->description }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="qa-card__meta">
                            <span class="qa-type-badge" data-t="{{ $action->action_type }}">{{ $actionTypes[$action->action_type] ?? $action->action_type }}</span>
                            @if(!$action->is_active)
                                <span class="qa-type-badge" style="background: rgba(100,116,139,0.16); color: #475569;">Disabled</span>
                            @endif
                        </div>
                        @if($action->action_type === 'answer' && $action->custom_answer)
                            <div class="qa-card__sub">“{{ Str::limit($action->custom_answer, 110) }}”</div>
                        @elseif($action->action_value)
                            <div class="qa-card__sub">{{ Str::limit($action->action_value, 80) }}</div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Live preview --}}
    <aside class="qa-preview" aria-label="Live widget preview">
        <div class="qa-preview__header" style="background: {{ $widgetPrimary }}">
            {{ optional($website->configuration)->bot_name ?: 'Bot' }} · Live preview
        </div>
        <div class="qa-preview__body">
            <div class="qa-preview__bubble">{{ optional($website->configuration)->welcome_message ?: 'Hi there! 👋 Use any of the buttons below to get started.' }}</div>
            @if($totalActive === 0)
                <div class="qa-preview__empty">Active actions will appear here exactly as visitors see them.</div>
            @else
                <div class="qa-preview__grid">
                    @foreach($actions->where('is_active', true) as $action)
                        <div class="qa-preview__btn" style="background: {{ $action->color ?: $widgetPrimary }}">
                            @if($action->icon)
                                <span class="qa-preview__btn-icon">{{ $action->icon }}</span>
                            @endif
                            <span class="qa-preview__btn-body">
                                <span class="qa-preview__btn-label">{{ $action->label }}</span>
                                @if($action->description)
                                    <span class="qa-preview__btn-desc">{{ $action->description }}</span>
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="qa-preview__footer">
            <span>Reorder updates instantly</span>
            @if($website->demo_slug)
                <a href="{{ route('demo.show', $website->demo_slug) }}" target="_blank" rel="noopener" style="color: var(--dash-accent, #2563eb); font-weight: 600;">Open demo ↗</a>
            @else
                <span>Visitors see this exactly</span>
            @endif
        </div>
    </aside>
</div>

{{-- Drawer / editor --}}
<div class="qa-drawer" id="qa-drawer" role="dialog" aria-modal="true" aria-labelledby="qa-drawer-title">
    <div class="qa-drawer__panel">
        <header class="qa-drawer__header">
            <span class="qa-drawer__title" id="qa-drawer-title">New quick action</span>
            <button type="button" class="qa-drawer__close" data-qa-close="">×</button>
        </header>
        <form method="POST" id="qa-form" class="qa-drawer__body">
            @csrf
            <input type="hidden" name="_method_override" id="qa-method">

            {{-- Step preview --}}
            <div class="qa-mini-preview">
                <button type="button" class="qa-preview__btn" id="qa-mini-btn" style="background: {{ $widgetPrimary }}; min-width: 200px; pointer-events: none;">
                    <span class="qa-preview__btn-icon" id="qa-mini-icon">💬</span>
                    <span class="qa-preview__btn-body">
                        <span class="qa-preview__btn-label" id="qa-mini-label">Button label</span>
                        <span class="qa-preview__btn-desc" id="qa-mini-desc" hidden></span>
                    </span>
                </button>
            </div>

            <div class="dash-field">
                <label class="dash-label" for="qa-label">Button label <span class="text-red-500">*</span></label>
                <input id="qa-label" name="label" type="text" maxlength="60" required class="dash-input w-full" placeholder="Get a quote">
            </div>

            <div class="dash-field">
                <label class="dash-label" for="qa-description">Short description (optional)</label>
                <input id="qa-description" name="description" type="text" maxlength="200" class="dash-input w-full" placeholder="Tell us about your project">
                <p class="text-xs dash-muted mt-1">Appears as a small caption inside the button.</p>
            </div>

            <div class="dash-field">
                <label class="dash-label">Icon</label>
                <div class="qa-icon-grid" id="qa-icon-grid">
                    <button type="button" class="qa-icon-btn-pick" data-icon="" title="No icon">∅</button>
                    @foreach($iconPresets as $preset)
                        <button type="button" class="qa-icon-btn-pick" data-icon="{{ $preset['value'] }}" title="{{ $preset['label'] }}">{{ $preset['value'] }}</button>
                    @endforeach
                </div>
                <input type="hidden" name="icon" id="qa-icon">
                <input type="text" class="dash-input w-full mt-2" id="qa-icon-custom" placeholder="…or paste any emoji / text" maxlength="64">
            </div>

            <div class="dash-field">
                <label class="dash-label">Color</label>
                <div class="qa-color-grid" id="qa-color-grid">
                    @foreach($palette as $swatch)
                        <button type="button" class="qa-color-swatch" data-color="{{ $swatch['value'] }}" title="{{ $swatch['name'] }}" style="background: {{ $swatch['value'] }}"></button>
                    @endforeach
                </div>
                <input type="hidden" name="color" id="qa-color">
            </div>

            <div class="dash-field">
                <label class="dash-label" for="qa-action-type">When clicked…</label>
                <select id="qa-action-type" name="action_type" class="dash-select w-full">
                    @foreach($actionTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="dash-field" id="qa-answer-field">
                <label class="dash-label" for="qa-custom-answer">Custom answer</label>
                <textarea id="qa-custom-answer" name="custom_answer" rows="4" maxlength="4000" class="dash-textarea w-full" placeholder="The bot will reply with this message exactly when the button is tapped."></textarea>
                <p class="text-xs dash-muted mt-1">Perfect for pre-written replies, instructions, or links.</p>
            </div>

            <div class="dash-field" id="qa-value-field" hidden>
                <label class="dash-label" for="qa-action-value">Destination</label>
                <input id="qa-action-value" name="action_value" type="text" maxlength="2048" class="dash-input w-full">
                <p class="text-xs dash-muted mt-1" id="qa-value-hint">URL or value to use when the button is tapped.</p>
            </div>

            <label class="dash-checkbox-row">
                <input type="checkbox" name="is_active" id="qa-active" value="1" checked>
                <span>Show this button in the widget</span>
            </label>

            <div class="flex gap-2 justify-end pt-2 border-t border-[var(--dash-border)]">
                <button type="button" class="dash-btn-ghost" data-qa-close="">Cancel</button>
                <button type="submit" class="dash-btn-primary" id="qa-submit">Save</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const websiteId = @json($website->id);
    const storeUrl = @json(route('websites.quick-actions.store', $website));
    const updateUrlTpl = @json(route('websites.quick-actions.update', [$website, 'ACTION_ID']));
    const toggleUrlTpl = @json(route('websites.quick-actions.toggle', [$website, 'ACTION_ID']));
    const reorderUrl = @json(route('websites.quick-actions.reorder', $website));
    const csrf = @json(csrf_token());
    const defaultColor = @json($widgetPrimary);
    const palette = @json($palette);
    const valueHints = {
        url: 'Full URL (https://…)',
        whatsapp: 'WhatsApp number with country code (no spaces)',
        email: 'Email address (visitor@…)',
        phone: 'Phone number with country code',
        message: 'Message text that will be sent as the visitor',
        internal: 'Internal key handled by your custom JS',
    };

    const drawer = document.getElementById('qa-drawer');
    const form = document.getElementById('qa-form');
    const title = document.getElementById('qa-drawer-title');
    const labelInput = document.getElementById('qa-label');
    const descInput = document.getElementById('qa-description');
    const iconHidden = document.getElementById('qa-icon');
    const iconCustom = document.getElementById('qa-icon-custom');
    const colorHidden = document.getElementById('qa-color');
    const typeSelect = document.getElementById('qa-action-type');
    const answerField = document.getElementById('qa-answer-field');
    const valueField = document.getElementById('qa-value-field');
    const valueInput = document.getElementById('qa-action-value');
    const valueHint = document.getElementById('qa-value-hint');
    const answerInput = document.getElementById('qa-custom-answer');
    const activeInput = document.getElementById('qa-active');
    const methodInput = document.getElementById('qa-method');
    const submitBtn = document.getElementById('qa-submit');

    const miniBtn = document.getElementById('qa-mini-btn');
    const miniIcon = document.getElementById('qa-mini-icon');
    const miniLabel = document.getElementById('qa-mini-label');
    const miniDesc = document.getElementById('qa-mini-desc');

    function openDrawer(payload) {
        form.reset();
        methodInput.value = '';
        if (payload) {
            title.textContent = 'Edit “' + (payload.label || 'quick action') + '”';
            labelInput.value = payload.label || '';
            descInput.value = payload.description || '';
            iconHidden.value = payload.icon || '';
            iconCustom.value = '';
            colorHidden.value = payload.color || '';
            typeSelect.value = payload.action_type || 'answer';
            answerInput.value = payload.custom_answer || '';
            valueInput.value = payload.action_value || '';
            activeInput.checked = !!payload.is_active;
            form.action = updateUrlTpl.replace('ACTION_ID', payload.id);
            methodInput.value = 'PUT';
            submitBtn.textContent = 'Save changes';
        } else {
            title.textContent = 'New quick action';
            iconHidden.value = '💬';
            colorHidden.value = defaultColor;
            typeSelect.value = 'answer';
            activeInput.checked = true;
            form.action = storeUrl;
            submitBtn.textContent = 'Add action';
        }
        syncIconUI();
        syncColorUI();
        syncTypeUI();
        syncPreview();
        drawer.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => labelInput.focus(), 50);
    }

    function closeDrawer() {
        drawer.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    function syncIconUI() {
        const current = iconHidden.value;
        document.querySelectorAll('.qa-icon-btn-pick').forEach((btn) => {
            btn.classList.toggle('is-active', (btn.dataset.icon || '') === current);
        });
    }
    function syncColorUI() {
        const current = colorHidden.value;
        document.querySelectorAll('.qa-color-swatch').forEach((s) => {
            s.classList.toggle('is-active', s.dataset.color === current);
        });
    }
    function syncTypeUI() {
        const type = typeSelect.value;
        const showAnswer = type === 'answer';
        const showValue = !showAnswer && type !== 'internal';
        answerField.hidden = !showAnswer;
        valueField.hidden = !(showValue || type === 'internal');
        if (valueHints[type]) valueHint.textContent = valueHints[type];
        valueInput.placeholder = valueHints[type] || '';
    }
    function syncPreview() {
        miniBtn.style.background = colorHidden.value || defaultColor;
        const icon = iconHidden.value || iconCustom.value;
        if (icon) { miniIcon.textContent = icon; miniIcon.hidden = false; } else { miniIcon.hidden = true; }
        miniLabel.textContent = labelInput.value.trim() || 'Button label';
        const desc = descInput.value.trim();
        if (desc) { miniDesc.textContent = desc; miniDesc.hidden = false; } else { miniDesc.hidden = true; }
    }

    document.querySelectorAll('[data-qa-open]').forEach((btn) => btn.addEventListener('click', () => openDrawer(null)));
    document.querySelectorAll('[data-qa-close]').forEach((btn) => btn.addEventListener('click', closeDrawer));
    drawer.addEventListener('click', (e) => { if (e.target === drawer) closeDrawer(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && drawer.classList.contains('is-open')) closeDrawer(); });

    document.querySelectorAll('[data-qa-edit]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const card = btn.closest('.qa-card');
            const payload = JSON.parse(card.dataset.payload);
            openDrawer(payload);
        });
    });

    document.getElementById('qa-icon-grid').addEventListener('click', (e) => {
        const btn = e.target.closest('.qa-icon-btn-pick');
        if (!btn) return;
        iconHidden.value = btn.dataset.icon || '';
        iconCustom.value = '';
        syncIconUI();
        syncPreview();
    });
    iconCustom.addEventListener('input', () => {
        if (iconCustom.value) {
            iconHidden.value = iconCustom.value;
            document.querySelectorAll('.qa-icon-btn-pick').forEach((b) => b.classList.remove('is-active'));
        }
        syncPreview();
    });
    document.getElementById('qa-color-grid').addEventListener('click', (e) => {
        const btn = e.target.closest('.qa-color-swatch');
        if (!btn) return;
        colorHidden.value = btn.dataset.color;
        syncColorUI();
        syncPreview();
    });
    typeSelect.addEventListener('change', () => { syncTypeUI(); });
    [labelInput, descInput].forEach((el) => el.addEventListener('input', syncPreview));

    form.addEventListener('submit', (e) => {
        if (methodInput.value === 'PUT') {
            if (!form.querySelector('input[name="_method"]')) {
                const m = document.createElement('input');
                m.type = 'hidden';
                m.name = '_method';
                m.value = 'PUT';
                form.appendChild(m);
            }
        }
    });

    // Toggle active
    document.querySelectorAll('[data-qa-toggle]').forEach((sw) => {
        sw.addEventListener('click', async () => {
            const id = sw.dataset.qaToggle;
            sw.disabled = true;
            try {
                const res = await fetch(toggleUrlTpl.replace('ACTION_ID', id), {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();
                const card = sw.closest('.qa-card');
                sw.classList.toggle('is-on', data.is_active);
                card.classList.toggle('is-inactive', !data.is_active);
                const payload = JSON.parse(card.dataset.payload);
                payload.is_active = data.is_active;
                card.dataset.payload = JSON.stringify(payload);
            } finally {
                sw.disabled = false;
            }
        });
    });

    // Drag & drop reorder
    const grid = document.getElementById('qa-grid');
    if (grid) {
        let dragging = null;
        grid.querySelectorAll('.qa-card').forEach((card) => {
            card.setAttribute('draggable', 'true');
            card.addEventListener('dragstart', (e) => {
                dragging = card;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            card.addEventListener('dragend', () => {
                card.classList.remove('is-dragging');
                dragging = null;
                persistOrder();
            });
            card.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (!dragging || dragging === card) return;
                const rect = card.getBoundingClientRect();
                const after = (e.clientY - rect.top) / rect.height > 0.5;
                if (after) {
                    card.parentNode.insertBefore(dragging, card.nextSibling);
                } else {
                    card.parentNode.insertBefore(dragging, card);
                }
            });
        });

        async function persistOrder() {
            const order = Array.from(grid.querySelectorAll('.qa-card')).map((c) => Number(c.dataset.id));
            await fetch(reorderUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ order }),
            });
        }
    }
})();
</script>
@endpush
@endsection
