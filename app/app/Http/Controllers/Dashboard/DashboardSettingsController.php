<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\AuthorizesTenantRole;
use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\AiConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardSettingsController extends Controller
{
    use AuthorizesTenantRole;

    public function index(Request $request, AiConfigService $ai): View
    {
        $this->ensureCanManageOrganization($request);

        $organization = $request->user()->organization;
        abort_unless($organization, 404);

        $tab = $request->query('tab', 'ai');
        $settings = $organization->settings ?? [];

        $isSuperAdmin = $request->user()->roleEnum()->isPlatformLevel();

        return view('dashboard.settings.index', [
            'tab' => in_array($tab, ['ai', 'dashboard', 'links'], true) ? $tab : 'ai',
            'organization' => $organization,
            'isSuperAdmin' => $isSuperAdmin,
            'aiConfigured' => $ai->isConfigured($organization),
            'aiKeyMask' => $ai->maskApiKey($ai->resolveApiKey($organization)),
            'aiKeySource' => $this->keySourceLabel($organization, $ai),
            'openaiModel' => $settings['openai_default_model'] ?? $ai->resolveDefaultModel($organization),
            'useOrgKey' => (bool) ($settings['use_org_openai_key'] ?? false),
            'hasOrgKey' => ! empty($settings['openai_api_key']),
            'platformModel' => PlatformSetting::get('openai_default_model', 'gpt-4o-mini'),
            'platformKeySet' => $ai->isConfigured(null),
            'platformBaseUrl' => PlatformSetting::get('openai_base_url', config('chatbot.openai.base_url')),
            'semanticEnabled' => (bool) PlatformSetting::get('semantic_search_enabled', config('chatbot.semantic_search.enabled')),
            'dashboardModules' => $ai->dashboardModules($organization),
            'maxPairs' => (int) PlatformSetting::get('training_max_qa_pairs', 8),
        ]);
    }

    public function updateAi(Request $request, AiConfigService $ai): RedirectResponse
    {
        $this->ensureCanManageOrganization($request);

        $organization = $request->user()->organization;
        abort_unless($organization, 404);

        $validated = $request->validate([
            'openai_api_key' => 'nullable|string|max:512',
            'openai_default_model' => 'nullable|string|max:64',
            'use_org_openai_key' => 'boolean',
            'clear_org_key' => 'boolean',
        ]);

        $settings = $organization->settings ?? [];
        $settings['use_org_openai_key'] = $request->boolean('use_org_openai_key');

        if ($request->boolean('clear_org_key')) {
            unset($settings['openai_api_key']);
            $settings['use_org_openai_key'] = false;
        } elseif ($request->filled('openai_api_key')) {
            $settings['openai_api_key'] = trim($validated['openai_api_key']);
            $settings['use_org_openai_key'] = true;
        }

        if ($request->filled('openai_default_model')) {
            $settings['openai_default_model'] = $validated['openai_default_model'];
        }

        $organization->update(['settings' => $settings]);

        return redirect()
            ->route('settings.index', ['tab' => 'ai'])
            ->with('success', 'AI settings saved for your organization.');
    }

    public function updatePlatformAi(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->roleEnum()->isPlatformLevel(), 403);

        $validated = $request->validate([
            'openai_api_key' => 'nullable|string|max:512',
            'openai_default_model' => 'required|string|max:64',
            'openai_base_url' => 'required|url|max:255',
            'semantic_search_enabled' => 'boolean',
            'training_max_qa_pairs' => 'nullable|integer|min:3|max:15',
            'clear_platform_key' => 'boolean',
        ]);

        if ($request->boolean('clear_platform_key')) {
            PlatformSetting::forget('openai_api_key');
        } elseif ($request->filled('openai_api_key')) {
            PlatformSetting::set('openai_api_key', $validated['openai_api_key']);
        }

        PlatformSetting::set('openai_default_model', $validated['openai_default_model']);
        PlatformSetting::set('openai_base_url', rtrim($validated['openai_base_url'], '/'));
        PlatformSetting::set('semantic_search_enabled', $request->boolean('semantic_search_enabled'));
        PlatformSetting::set('training_max_qa_pairs', (int) ($validated['training_max_qa_pairs'] ?? 8));

        return redirect()
            ->route('settings.index', ['tab' => 'ai'])
            ->with('success', 'Platform AI settings updated.');
    }

    public function updateDashboardModules(Request $request, AiConfigService $ai): RedirectResponse
    {
        $this->ensureCanManageOrganization($request);

        $organization = $request->user()->organization;
        abort_unless($organization, 404);

        $modules = $ai->defaultDashboardModules();
        $input = [];
        foreach (array_keys($modules) as $key) {
            $input[$key] = $request->boolean("modules.{$key}");
        }

        $settings = $organization->settings ?? [];
        $settings['dashboard_modules'] = $input;
        $organization->update(['settings' => $settings]);

        return redirect()
            ->route('settings.index', ['tab' => 'dashboard'])
            ->with('success', 'Dashboard modules updated.');
    }

    public function testAi(Request $request, AiConfigService $ai): RedirectResponse
    {
        $this->ensureCanManageOrganization($request);

        $organization = $request->user()->organization;
        $usePlatform = $request->boolean('platform') && $request->user()->roleEnum()->isPlatformLevel();

        $result = $ai->testConnection($usePlatform ? null : $organization);

        return redirect()
            ->route('settings.index', ['tab' => 'ai'])
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    protected function keySourceLabel($organization, AiConfigService $ai): string
    {
        $settings = $organization->settings ?? [];
        if (! empty($settings['use_org_openai_key']) && ! empty($settings['openai_api_key'])) {
            return 'Workspace key';
        }
        if (PlatformSetting::get('openai_api_key')) {
            return 'Platform default';
        }
        if (config('chatbot.openai.api_key')) {
            return 'Environment (.env)';
        }
        if (! empty($settings['openai_api_key'])) {
            return 'Workspace key (fallback)';
        }

        return 'Not configured';
    }
}
