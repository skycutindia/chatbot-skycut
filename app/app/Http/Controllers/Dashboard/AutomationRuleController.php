<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ChatAutomationRule;
use App\Models\Department;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AutomationRuleController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        $rules = ChatAutomationRule::query()
            ->where('organization_id', $orgId)
            ->with('website')
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get();

        $websites = Website::query()
            ->where('organization_id', $orgId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $departments = Department::query()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('dashboard.automation-rules.index', compact('rules', 'websites', 'departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRule($request);
        $this->assertWebsiteAccess($request, $validated['website_id'] ?? null);
        $this->assertActionConfig($request, $validated);

        ChatAutomationRule::create([
            'organization_id' => $request->user()->organization_id,
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Automation rule created.');
    }

    public function update(Request $request, ChatAutomationRule $automationRule): RedirectResponse
    {
        abort_unless($automationRule->organization_id === $request->user()->organization_id, 403);

        $validated = $this->validateRule($request);
        $this->assertWebsiteAccess($request, $validated['website_id'] ?? null);
        $this->assertActionConfig($request, $validated);

        $automationRule->update([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Automation rule updated.');
    }

    public function destroy(ChatAutomationRule $automationRule): RedirectResponse
    {
        abort_unless($automationRule->organization_id === auth()->user()->organization_id, 403);
        $automationRule->delete();

        return back()->with('success', 'Automation rule removed.');
    }

    /** @return array<string, mixed> */
    protected function validateRule(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'website_id' => 'nullable|exists:websites,id',
            'trigger_type' => 'required|in:keyword,new_conversation,inactive',
            'action_type' => 'required|in:assign_department,assign_agent,add_tag,set_priority,close,capture_lead,request_survey',
            'priority' => 'nullable|integer|min:0|max:100',
            'keywords' => 'nullable|string|max:500',
            'inactive_minutes' => 'nullable|integer|min:5|max:10080',
            'tag' => 'nullable|string|max:64',
            'department_id' => 'nullable|exists:departments,id',
            'priority_level' => 'nullable|in:low,normal,medium,high,urgent',
        ]);

        $triggerConfig = match ($validated['trigger_type']) {
            'keyword' => [
                'keywords' => array_values(array_filter(array_map('trim', explode(',', $validated['keywords'] ?? '')))),
            ],
            'inactive' => [
                'minutes' => (int) ($validated['inactive_minutes'] ?? 60),
            ],
            default => [],
        };

        if ($validated['trigger_type'] === 'keyword' && empty($triggerConfig['keywords'])) {
            throw ValidationException::withMessages(['keywords' => 'At least one keyword is required.']);
        }

        $actionConfig = match ($validated['action_type']) {
            'assign_department', 'assign_agent' => ['department_id' => $validated['department_id'] ?? null],
            'add_tag' => ['tag' => $validated['tag'] ?? ''],
            'set_priority' => ['priority' => $validated['priority_level'] ?? 'normal'],
            default => [],
        };

        return [
            'website_id' => $validated['website_id'] ?? null,
            'name' => $validated['name'],
            'trigger_type' => $validated['trigger_type'],
            'trigger_config' => $triggerConfig,
            'action_type' => $validated['action_type'],
            'action_config' => $actionConfig,
            'priority' => (int) ($validated['priority'] ?? 0),
        ];
    }

    protected function assertWebsiteAccess(Request $request, ?int $websiteId): void
    {
        if (! $websiteId) {
            return;
        }

        abort_unless(
            Website::where('id', $websiteId)->where('organization_id', $request->user()->organization_id)->exists(),
            403
        );
    }

    /** @param array<string, mixed> $validated */
    protected function assertActionConfig(Request $request, array $validated): void
    {
        if (in_array($validated['action_type'], ['assign_department', 'assign_agent'], true)) {
            $deptId = $validated['action_config']['department_id'] ?? null;
            if ($validated['action_type'] === 'assign_department' && ! $deptId) {
                throw ValidationException::withMessages(['department_id' => 'Department is required for this action.']);
            }
            if ($deptId) {
                abort_unless(
                    Department::where('id', $deptId)->where('organization_id', $request->user()->organization_id)->exists(),
                    403
                );
            }
        }

        if ($validated['action_type'] === 'add_tag' && empty($validated['action_config']['tag'])) {
            throw ValidationException::withMessages(['tag' => 'Tag name is required.']);
        }
    }
}
