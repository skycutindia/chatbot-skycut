@php
    $isEdit = (bool) $rule;
    $keywords = $isEdit ? implode(', ', $rule->trigger_config['keywords'] ?? []) : '';
    $inactiveMinutes = $isEdit ? ($rule->trigger_config['minutes'] ?? 60) : 60;
    $tag = $isEdit ? ($rule->action_config['tag'] ?? '') : '';
    $departmentId = $isEdit ? ($rule->action_config['department_id'] ?? '') : '';
    $priorityLevel = $isEdit ? ($rule->action_config['priority'] ?? 'normal') : 'normal';
@endphp
<form method="POST" action="{{ $isEdit ? route('automation-rules.update', $rule) : route('automation-rules.store') }}" class="space-y-3 {{ $isEdit ? 'dash-card p-4' : '' }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="grid md:grid-cols-2 gap-3">
        <div class="dash-field">
            <label class="dash-label">Rule name</label>
            <input name="name" value="{{ old('name', $rule?->name ?? '') }}" required class="dash-input w-full" placeholder="e.g. Billing keyword → Billing dept">
        </div>
        <div class="dash-field">
            <label class="dash-label">Website scope</label>
            <select name="website_id" class="dash-select w-full">
                <option value="">All websites</option>
                @foreach($websites as $site)
                    <option value="{{ $site->id }}" @selected(old('website_id', $rule?->website_id ?? '') == $site->id)>{{ $site->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-3">
        <div class="dash-field">
            <label class="dash-label">When (trigger)</label>
            <select name="trigger_type" class="dash-select w-full" data-trigger-select required>
                <option value="keyword" @selected(old('trigger_type', $rule?->trigger_type ?? 'keyword') === 'keyword')>Message contains keyword</option>
                <option value="new_conversation" @selected(old('trigger_type', $rule?->trigger_type ?? '') === 'new_conversation')>New conversation started</option>
                <option value="inactive" @selected(old('trigger_type', $rule?->trigger_type ?? '') === 'inactive')>Conversation inactive (scheduled)</option>
            </select>
        </div>
        <div class="dash-field">
            <label class="dash-label">Then (action)</label>
            <select name="action_type" class="dash-select w-full" data-action-select required>
                <option value="add_tag" @selected(old('action_type', $rule?->action_type ?? 'add_tag') === 'add_tag')>Add tag</option>
                <option value="assign_department" @selected(old('action_type', $rule?->action_type ?? '') === 'assign_department')>Assign department</option>
                <option value="assign_agent" @selected(old('action_type', $rule?->action_type ?? '') === 'assign_agent')>Auto-assign agent</option>
                <option value="set_priority" @selected(old('action_type', $rule?->action_type ?? '') === 'set_priority')>Set priority</option>
                <option value="close" @selected(old('action_type', $rule?->action_type ?? '') === 'close')>Close conversation</option>
                <option value="capture_lead" @selected(old('action_type', $rule?->action_type ?? '') === 'capture_lead')>Save as lead</option>
                <option value="request_survey" @selected(old('action_type', $rule?->action_type ?? '') === 'request_survey')>Request CSAT survey</option>
            </select>
        </div>
    </div>

    <div data-trigger-panel="keyword" class="{{ old('trigger_type', $rule?->trigger_type ?? 'keyword') === 'keyword' ? '' : 'hidden' }}">
        <div class="dash-field">
            <label class="dash-label">Keywords (comma-separated)</label>
            <input name="keywords" value="{{ old('keywords', $keywords) }}" class="dash-input w-full" placeholder="billing, invoice, refund">
        </div>
    </div>

    <div data-trigger-panel="inactive" class="{{ old('trigger_type', $rule?->trigger_type ?? '') === 'inactive' ? '' : 'hidden' }}">
        <div class="dash-field">
            <label class="dash-label">Inactive for (minutes)</label>
            <input type="number" name="inactive_minutes" min="5" max="10080" value="{{ old('inactive_minutes', $inactiveMinutes) }}" class="dash-input w-full">
            <p class="text-xs dash-muted mt-1">Checked hourly by <code>php artisan chat:close-inactive</code></p>
        </div>
    </div>

    <div data-action-panel="add_tag" class="{{ old('action_type', $rule?->action_type ?? 'add_tag') === 'add_tag' ? '' : 'hidden' }}">
        <div class="dash-field">
            <label class="dash-label">Tag name</label>
            <input name="tag" value="{{ old('tag', $tag) }}" class="dash-input w-full" placeholder="billing">
        </div>
    </div>

    <div data-action-panel="assign_department" class="{{ old('action_type', $rule?->action_type ?? '') === 'assign_department' ? '' : 'hidden' }}">
        <div class="dash-field">
            <label class="dash-label">Department</label>
            <select name="department_id" class="dash-select w-full">
                <option value="">Select department</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected(old('department_id', $departmentId) == $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div data-action-panel="assign_agent" class="{{ old('action_type', $rule?->action_type ?? '') === 'assign_agent' ? '' : 'hidden' }}">
        <div class="dash-field">
            <label class="dash-label">Department (optional, for routing)</label>
            <select name="department_id" class="dash-select w-full">
                <option value="">Any department</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected(old('department_id', $departmentId) == $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div data-action-panel="set_priority" class="{{ old('action_type', $rule?->action_type ?? '') === 'set_priority' ? '' : 'hidden' }}">
        <div class="dash-field">
            <label class="dash-label">Priority level</label>
            <select name="priority_level" class="dash-select w-full">
                @foreach(['low','normal','medium','high','urgent'] as $p)
                    <option value="{{ $p }}" @selected(old('priority_level', $priorityLevel) === $p)>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-3">
        <div class="dash-field">
            <label class="dash-label">Priority (higher runs first)</label>
            <input type="number" name="priority" min="0" max="100" value="{{ old('priority', $rule?->priority ?? 0) }}" class="dash-input w-full">
        </div>
        <div class="dash-field flex items-end">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rule?->is_active ?? true))>
                Active
            </label>
        </div>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="dash-btn-primary dash-btn-sm">{{ $isEdit ? 'Save changes' : 'Create rule' }}</button>
        @if($isEdit)
            <button type="button" class="dash-btn-secondary dash-btn-sm" data-cancel-rule="{{ $rule->id }}">Cancel</button>
        @endif
    </div>
</form>
