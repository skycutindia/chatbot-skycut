@extends('layouts.app')

@section('title', 'Automation rules')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Live chat</p>
        <h1 class="dash-page-title">Automation rules</h1>
        <p class="dash-page-sub">Auto-assign, tag, close inactive chats, capture leads, and trigger surveys</p>
    </div>
</div>
@endsection

@section('content')
<div class="dash-page-medium">

    @if($rules->isEmpty())
        <div class="dash-empty mt-6">
            <p class="font-medium">No automation rules yet</p>
            <p class="text-sm dash-muted mt-1">Create rules below to automate repetitive inbox workflows.</p>
        </div>
    @else
        <div class="dash-table-wrap mt-6">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Rule</th>
                        <th>Trigger</th>
                        <th>Action</th>
                        <th>Scope</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rules as $rule)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $rule->name }}</p>
                                <p class="text-xs dash-muted">Priority {{ $rule->priority }}</p>
                            </td>
                            <td class="text-sm">
                                {{ $rule->triggerLabel() }}
                                @if($rule->trigger_type === 'keyword')
                                    <p class="text-xs dash-muted">{{ implode(', ', $rule->trigger_config['keywords'] ?? []) }}</p>
                                @elseif($rule->trigger_type === 'inactive')
                                    <p class="text-xs dash-muted">After {{ $rule->trigger_config['minutes'] ?? 60 }} min</p>
                                @endif
                            </td>
                            <td class="text-sm">
                                {{ $rule->actionLabel() }}
                                @if($rule->action_type === 'add_tag')
                                    <p class="text-xs dash-muted">{{ $rule->action_config['tag'] ?? '' }}</p>
                                @elseif($rule->action_type === 'set_priority')
                                    <p class="text-xs dash-muted">{{ ucfirst($rule->action_config['priority'] ?? '') }}</p>
                                @elseif(in_array($rule->action_type, ['assign_department', 'assign_agent']))
                                    @php $deptId = $rule->action_config['department_id'] ?? null; @endphp
                                    @if($deptId)
                                        <p class="text-xs dash-muted">{{ $departments->firstWhere('id', $deptId)?->name }}</p>
                                    @endif
                                @endif
                            </td>
                            <td class="text-sm dash-muted">{{ $rule->website?->name ?? 'All websites' }}</td>
                            <td>
                                @if($rule->is_active)
                                    <span class="dash-badge dash-badge-success">Active</span>
                                @else
                                    <span class="dash-badge dash-badge-muted">Off</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <button type="button" class="dash-btn-secondary dash-btn-sm" data-edit-rule="{{ $rule->id }}">Edit</button>
                                <form method="POST" action="{{ route('automation-rules.destroy', $rule) }}" class="inline" onsubmit="return confirm('Delete this rule?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dash-btn-danger dash-btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr id="rule-edit-{{ $rule->id }}" class="hidden">
                            <td colspan="6">
                                @include('dashboard.automation-rules.partials.form', ['rule' => $rule, 'websites' => $websites, 'departments' => $departments])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="dash-card mt-8">
        <div class="dash-card-body">
            <h2 class="dash-form-section-title">Add automation rule</h2>
            @include('dashboard.automation-rules.partials.form', ['rule' => null, 'websites' => $websites, 'departments' => $departments])
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-edit-rule]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('rule-edit-' + btn.dataset.editRule)?.classList.remove('hidden');
    });
});
document.querySelectorAll('[data-cancel-rule]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('rule-edit-' + btn.dataset.cancelRule)?.classList.add('hidden');
    });
});
document.querySelectorAll('[data-trigger-select]').forEach(sel => {
    const sync = () => {
        const form = sel.closest('form');
        if (!form) return;
        form.querySelectorAll('[data-trigger-panel]').forEach(p => p.classList.add('hidden'));
        form.querySelector('[data-trigger-panel="' + sel.value + '"]')?.classList.remove('hidden');
    };
    sel.addEventListener('change', sync);
    sync();
});
document.querySelectorAll('[data-action-select]').forEach(sel => {
    const sync = () => {
        const form = sel.closest('form');
        if (!form) return;
        form.querySelectorAll('[data-action-panel]').forEach(p => p.classList.add('hidden'));
        form.querySelector('[data-action-panel="' + sel.value + '"]')?.classList.remove('hidden');
    };
    sel.addEventListener('change', sync);
    sync();
});
</script>
@endsection
