<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\AuthorizesTenantRole;
use App\Http\Controllers\Controller;
use App\Models\QuickAction;
use App\Models\Website;
use App\Services\WidgetConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuickActionController extends Controller
{
    use AuthorizesTenantRole;

    private const ACTION_RULES = 'required|in:answer,message,url,whatsapp,email,phone,internal';

    public function index(Request $request, Website $website): View
    {
        $this->ensureWebsiteInOrganization($request, $website);

        $website->load('quickActions');
        $actions = $website->quickActions()->orderBy('sort_order')->orderBy('id')->get();

        return view('dashboard.websites.quick-actions', [
            'website' => $website,
            'actions' => $actions,
            'actionTypes' => QuickAction::ACTION_TYPES,
            'palette' => $this->palette(),
            'iconPresets' => $this->iconPresets(),
        ]);
    }

    public function store(Request $request, Website $website, WidgetConfigService $config): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $data = $this->validatePayload($request);
        $data['sort_order'] = (int) ($website->quickActions()->max('sort_order') ?? 0) + 10;

        $website->quickActions()->create($data);
        $config->invalidate($website);

        return back()->with('success', 'Quick action added — visitors see it instantly.');
    }

    public function update(Request $request, Website $website, QuickAction $quickAction, WidgetConfigService $config): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);
        abort_unless($quickAction->website_id === $website->id, 404);

        $quickAction->update($this->validatePayload($request));
        $config->invalidate($website);

        return back()->with('success', "“{$quickAction->label}” updated.");
    }

    public function toggle(Request $request, Website $website, QuickAction $quickAction, WidgetConfigService $config): JsonResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);
        abort_unless($quickAction->website_id === $website->id, 404);

        $quickAction->update(['is_active' => ! $quickAction->is_active]);
        $config->invalidate($website);

        return response()->json([
            'id' => $quickAction->id,
            'is_active' => $quickAction->is_active,
        ]);
    }

    public function duplicate(Request $request, Website $website, QuickAction $quickAction, WidgetConfigService $config): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);
        abort_unless($quickAction->website_id === $website->id, 404);

        $copy = $quickAction->replicate();
        $copy->label = $quickAction->label.' (copy)';
        $copy->sort_order = (int) ($website->quickActions()->max('sort_order') ?? 0) + 10;
        $copy->save();

        $config->invalidate($website);

        return back()->with('success', 'Quick action duplicated.');
    }

    public function reorder(Request $request, Website $website, WidgetConfigService $config): JsonResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        foreach ($validated['order'] as $index => $id) {
            $website->quickActions()->whereKey($id)->update(['sort_order' => ($index + 1) * 10]);
        }

        $config->invalidate($website);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, Website $website, QuickAction $quickAction, WidgetConfigService $config): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);
        abort_unless($quickAction->website_id === $website->id, 404);

        $quickAction->delete();
        $config->invalidate($website);

        return back()->with('success', 'Quick action removed.');
    }

    /** @return array<string, mixed> */
    protected function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'label' => 'required|string|max:60',
            'description' => 'nullable|string|max:200',
            'icon' => 'nullable|string|max:64',
            'color' => 'nullable|string|max:32',
            'action_type' => self::ACTION_RULES,
            'action_value' => 'nullable|string|max:2048',
            'custom_answer' => 'nullable|string|max:4000',
            'is_active' => 'boolean',
        ]);

        if ($validated['action_type'] === 'answer') {
            $request->validate([
                'custom_answer' => 'required|string|max:4000',
            ], [
                'custom_answer.required' => 'Custom answer is required when action type is "Custom answer".',
            ]);
        } elseif (in_array($validated['action_type'], ['url', 'whatsapp', 'email', 'phone', 'message'], true)) {
            $request->validate([
                'action_value' => 'required|string|max:2048',
            ]);
        }

        return [
            'label' => trim($validated['label']),
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? null,
            'action_type' => $validated['action_type'],
            'action_value' => $validated['action_value'] ?? null,
            'custom_answer' => $validated['custom_answer'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    /** @return list<array{name:string,value:string}> */
    protected function palette(): array
    {
        return [
            ['name' => 'Slate', 'value' => '#0f172a'],
            ['name' => 'Blue', 'value' => '#2563eb'],
            ['name' => 'Indigo', 'value' => '#4f46e5'],
            ['name' => 'Violet', 'value' => '#7c3aed'],
            ['name' => 'Pink', 'value' => '#db2777'],
            ['name' => 'Rose', 'value' => '#e11d48'],
            ['name' => 'Orange', 'value' => '#ea580c'],
            ['name' => 'Amber', 'value' => '#d97706'],
            ['name' => 'Emerald', 'value' => '#059669'],
            ['name' => 'Teal', 'value' => '#0d9488'],
            ['name' => 'Cyan', 'value' => '#0891b2'],
            ['name' => 'Gray', 'value' => '#475569'],
        ];
    }

    /** @return list<array{label:string,value:string}> */
    protected function iconPresets(): array
    {
        return [
            ['label' => 'Chat', 'value' => '💬'],
            ['label' => 'Quote', 'value' => '💸'],
            ['label' => 'Calendar', 'value' => '📅'],
            ['label' => 'Phone', 'value' => '📞'],
            ['label' => 'WhatsApp', 'value' => '📱'],
            ['label' => 'Mail', 'value' => '✉️'],
            ['label' => 'Sparkles', 'value' => '✨'],
            ['label' => 'Bolt', 'value' => '⚡'],
            ['label' => 'Star', 'value' => '⭐'],
            ['label' => 'Help', 'value' => '❓'],
            ['label' => 'Info', 'value' => 'ℹ️'],
            ['label' => 'Cart', 'value' => '🛒'],
            ['label' => 'Gift', 'value' => '🎁'],
            ['label' => 'Rocket', 'value' => '🚀'],
            ['label' => 'Map pin', 'value' => '📍'],
            ['label' => 'Tools', 'value' => '🛠️'],
        ];
    }
}
