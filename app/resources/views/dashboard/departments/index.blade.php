@extends('layouts.app')

@section('title', 'Departments')

@section('page-header')
<div class="dash-page-header">
    <div>
        <p class="dash-page-eyebrow">Live chat</p>
        <h1 class="dash-page-title">Departments</h1>
    </div>
</div>
@endsection

@section('page-toolbar')
<a href="{{ route('inbox.index') }}" class="dash-btn-secondary dash-btn-sm">Open inbox</a>
@endsection

@section('content')
<div class="dash-page-medium">

    @if($departments->isEmpty())
        <div class="dash-empty mt-6">
            <p class="font-medium">No departments yet</p>
            <p class="text-sm dash-muted mt-1">Create teams below, then assign agents for routed auto-assignment.</p>
        </div>
    @else
        <div class="dash-table-wrap mt-6">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Agents</th>
                        <th>Chats</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departments as $department)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $department->name }}</p>
                                @if($department->description)
                                    <p class="text-xs dash-muted mt-0.5">{{ Str::limit($department->description, 80) }}</p>
                                @endif
                                <p class="text-xs dash-muted mt-0.5">{{ $department->slug }}</p>
                            </td>
                            <td>
                                @if($department->agents_count === 0)
                                    <span class="dash-badge dash-badge-warning">No agents</span>
                                @else
                                    <span class="text-sm">{{ $department->agents_count }} assigned</span>
                                    <p class="text-xs dash-muted mt-0.5">{{ $department->agents->pluck('name')->join(', ') }}</p>
                                @endif
                            </td>
                            <td>{{ $department->conversations_count }}</td>
                            <td>
                                @if($department->is_active)
                                    <span class="dash-badge dash-badge-success">Active</span>
                                @else
                                    <span class="dash-badge dash-badge-muted">Inactive</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <button type="button" class="dash-btn-secondary dash-btn-sm" data-edit-dept="{{ $department->id }}">Edit</button>
                                <button type="button" class="dash-btn-secondary dash-btn-sm" data-agents-dept="{{ $department->id }}">Agents</button>
                                <form method="POST" action="{{ route('departments.destroy', $department) }}" class="inline" onsubmit="return confirm('Remove this department? Active chats will be unassigned from it.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dash-btn-danger dash-btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr id="dept-edit-{{ $department->id }}" class="hidden">
                            <td colspan="5">
                                <form method="POST" action="{{ route('departments.update', $department) }}" class="dash-card">
                                    <div class="dash-card-body grid md:grid-cols-2 gap-3">
                                        @csrf @method('PUT')
                                        <div class="dash-field">
                                            <label class="dash-label">Name</label>
                                            <input name="name" value="{{ $department->name }}" required class="dash-input w-full">
                                        </div>
                                        <div class="dash-field flex items-end">
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="checkbox" name="is_active" value="1" @checked($department->is_active)>
                                                Active
                                            </label>
                                        </div>
                                        <div class="dash-field md:col-span-2">
                                            <label class="dash-label">Description</label>
                                            <textarea name="description" rows="2" class="dash-textarea w-full">{{ $department->description }}</textarea>
                                        </div>
                                        <div class="md:col-span-2 flex gap-2">
                                            <button type="submit" class="dash-btn-primary dash-btn-sm">Save changes</button>
                                            <button type="button" class="dash-btn-secondary dash-btn-sm" data-cancel-dept="{{ $department->id }}">Cancel</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <tr id="dept-agents-{{ $department->id }}" class="hidden">
                            <td colspan="5">
                                <form method="POST" action="{{ route('departments.agents.sync', $department) }}" class="dash-card">
                                    <div class="dash-card-body space-y-3">
                                        @csrf @method('PUT')
                                        <h3 class="font-semibold text-sm">Agents in {{ $department->name }}</h3>
                                        <p class="text-xs dash-muted">Only these agents receive auto-assigned chats routed to this department.</p>
                                        @if($agents->isEmpty())
                                            <p class="text-sm dash-muted">No live-chat agents in your organization yet.</p>
                                        @else
                                            <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-2">
                                                @foreach($agents as $agent)
                                                    <label class="flex items-center gap-2 text-sm border border-[var(--dash-border)] rounded-lg px-3 py-2">
                                                        <input type="checkbox" name="user_ids[]" value="{{ $agent->id }}"
                                                            @checked($department->agents->contains('id', $agent->id))>
                                                        <span>{{ $agent->name }}</span>
                                                        <span class="text-xs dash-muted ml-auto">{{ ucfirst(str_replace('_', ' ', $agent->role)) }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endif
                                        <div class="flex gap-2">
                                            <button type="submit" class="dash-btn-primary dash-btn-sm">Save agents</button>
                                            <button type="button" class="dash-btn-secondary dash-btn-sm" data-cancel-agents="{{ $department->id }}">Cancel</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <form method="POST" action="{{ route('departments.store') }}" class="dash-card mt-8">
        <div class="dash-card-body space-y-3">
            @csrf
            <h2 class="dash-form-section-title">Add department</h2>
            <div class="grid md:grid-cols-2 gap-3">
                <div class="dash-field">
                    <label class="dash-label" for="dept-name">Name</label>
                    <input id="dept-name" name="name" placeholder="e.g. Sales, Support, Billing" required class="dash-input w-full">
                </div>
                <div class="dash-field flex items-end">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active
                    </label>
                </div>
            </div>
            <div class="dash-field">
                <label class="dash-label" for="dept-desc">Description (optional)</label>
                <textarea id="dept-desc" name="description" rows="2" placeholder="What this team handles..." class="dash-textarea w-full"></textarea>
            </div>
            <button type="submit" class="dash-btn-primary">Create department</button>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('[data-edit-dept]').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.editDept;
        document.getElementById('dept-agents-' + id)?.classList.add('hidden');
        document.getElementById('dept-edit-' + id)?.classList.remove('hidden');
    });
});
document.querySelectorAll('[data-agents-dept]').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.agentsDept;
        document.getElementById('dept-edit-' + id)?.classList.add('hidden');
        document.getElementById('dept-agents-' + id)?.classList.remove('hidden');
    });
});
document.querySelectorAll('[data-cancel-dept]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('dept-edit-' + btn.dataset.cancelDept)?.classList.add('hidden');
    });
});
document.querySelectorAll('[data-cancel-agents]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('dept-agents-' + btn.dataset.cancelAgents)?.classList.add('hidden');
    });
});
</script>
@endsection
