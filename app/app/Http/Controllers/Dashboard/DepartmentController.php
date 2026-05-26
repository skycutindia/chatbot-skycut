<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $departments = Department::query()
            ->where('organization_id', $request->user()->organization_id)
            ->with(['agents:id,name,role'])
            ->withCount(['conversations', 'agents'])
            ->orderBy('name')
            ->get();

        $agents = User::query()
            ->where('organization_id', $request->user()->organization_id)
            ->where('is_active', true)
            ->whereIn('role', ['agent', 'manager', 'admin', 'owner'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('dashboard.departments.index', compact('departments', 'agents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $slug = $this->uniqueSlug($request->user()->organization_id, Str::slug($validated['name']));

        Department::create([
            'organization_id' => $request->user()->organization_id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        abort_unless($department->organization_id === $request->user()->organization_id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $slug = $department->slug;
        if ($validated['name'] !== $department->name) {
            $slug = $this->uniqueSlug($department->organization_id, Str::slug($validated['name']), $department->id);
        }

        $department->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Department updated.');
    }

    public function syncAgents(Request $request, Department $department): RedirectResponse
    {
        abort_unless($department->organization_id === $request->user()->organization_id, 403);

        $validated = $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $userIds = User::query()
            ->where('organization_id', $request->user()->organization_id)
            ->whereIn('role', ['agent', 'manager', 'admin', 'owner'])
            ->whereIn('id', $validated['user_ids'] ?? [])
            ->pluck('id');

        $department->agents()->sync($userIds);

        return back()->with('success', 'Department agents updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        abort_unless($department->organization_id === auth()->user()->organization_id, 403);

        Conversation::query()
            ->where('department_id', $department->id)
            ->update(['department_id' => null]);

        $department->delete();

        return back()->with('success', 'Department removed.');
    }

    protected function uniqueSlug(int $organizationId, string $base, ?int $ignoreId = null): string
    {
        $slug = $base ?: 'department';
        $suffix = 1;

        while (
            Department::query()
                ->where('organization_id', $organizationId)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
