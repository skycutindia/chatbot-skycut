<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\AuthorizesTenantRole;
use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Services\WidgetConfigService;
use App\Services\WidgetPluginExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebsiteController extends Controller
{
    use AuthorizesTenantRole;

    public function index(Request $request): View
    {
        $orgId = $request->user()->organization_id;
        $websites = Website::where('organization_id', $orgId)
            ->with('configuration')
            ->withCount([
                'conversations',
                'leads',
                'conversations as open_chats_count' => fn ($q) => $q->whereIn('status', ['open', 'awaiting_agent']),
                'unansweredQuestions as unanswered_open_count' => fn ($q) => $q->where('status', 'open'),
            ])
            ->withMax('conversations', 'last_message_at')
            ->latest()
            ->paginate(15);

        $websiteIds = Website::where('organization_id', $orgId)->pluck('id');
        $summary = [
            'total' => $websiteIds->count(),
            'active' => Website::where('organization_id', $orgId)->where('is_active', true)->count(),
            'conversations' => \App\Models\Conversation::whereIn('website_id', $websiteIds)->count(),
            'leads' => \App\Models\Lead::whereIn('website_id', $websiteIds)->count(),
            'open_chats' => \App\Models\Conversation::whereIn('website_id', $websiteIds)
                ->whereIn('status', ['open', 'awaiting_agent'])->count(),
        ];

        return view('dashboard.websites.index', compact('websites', 'summary'));
    }

    public function create(): View
    {
        $this->ensureCanManageWebsites(request());

        return view('dashboard.websites.wizard-step1');
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|url|max:2048',
            'domain' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:120',
            'language' => 'nullable|string|max:10',
            'contact_email' => 'nullable|email|max:255',
            'logo_url' => 'nullable|url|max:2048',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $request->session()->put('website_wizard', $validated);

        return redirect()->route('websites.create.bot');
    }

    public function createBot(Request $request): View|RedirectResponse
    {
        $this->ensureCanManageWebsites($request);

        if (! $request->session()->has('website_wizard')) {
            return redirect()->route('websites.create');
        }

        return view('dashboard.websites.wizard-step2', [
            'wizard' => $request->session()->get('website_wizard'),
        ]);
    }

    public function store(Request $request, WidgetConfigService $configService, WidgetPluginExportService $pluginExport): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);

        $step1 = $request->session()->get('website_wizard');
        if (! $step1) {
            return redirect()->route('websites.create');
        }

        $validated = $request->validate([
            'bot_name' => 'required|string|max:120',
            'welcome_message' => 'nullable|string|max:2000',
            'bot_description' => 'nullable|string|max:2000',
            'avatar_url' => 'nullable|url|max:2048',
            'locale' => 'nullable|string|max:10',
            'primary_color' => 'required|string|max:20',
            'secondary_color' => 'required|string|max:20',
            'ai_tone' => 'nullable|string|max:64',
            'fallback_message' => 'nullable|string|max:2000',
            'typing_animation' => 'boolean',
            'bot_online' => 'boolean',
            'ai_enabled' => 'boolean',
        ]);

        $website = Website::create([
            'organization_id' => $request->user()->organization_id,
            'name' => $step1['name'],
            'url' => $step1['url'] ?? null,
            'category' => $step1['category'] ?? null,
            'language' => $step1['language'] ?? 'en',
            'contact_email' => $step1['contact_email'] ?? null,
            'logo_url' => $step1['logo_url'] ?? null,
            'is_active' => $step1['is_active'] ?? true,
            'widget_enabled' => true,
        ]);

        if (! empty($step1['domain'])) {
            $website->allowedDomains()->create(['domain' => $step1['domain']]);
        }

        $website->configuration->update([
            'bot_name' => $validated['bot_name'],
            'bot_description' => $validated['bot_description'] ?? null,
            'avatar_url' => $validated['avatar_url'] ?? null,
            'primary_color' => $validated['primary_color'],
            'secondary_color' => $validated['secondary_color'],
            'welcome_message' => $validated['welcome_message'] ?? 'Hi! How can we help you today?',
            'fallback_message' => $validated['fallback_message'] ?? null,
            'locale' => $validated['locale'] ?? $step1['language'] ?? 'en',
            'ai_tone' => $validated['ai_tone'] ?? 'professional',
            'typing_animation' => $request->boolean('typing_animation', true),
            'bot_online' => $request->boolean('bot_online', true),
            'ai_enabled' => $request->boolean('ai_enabled', true),
        ]);

        $configService->invalidate($website);
        $pluginExport->persistPackage($website);
        $request->session()->forget('website_wizard');

        return redirect()
            ->route('websites.embed', $website)
            ->with('success', 'Chatbot created. Download your plugin or copy the embed code.');
    }

    public function show(Request $request, Website $website): View
    {
        $this->ensureWebsiteInOrganization($request, $website);

        $website->load(['configuration', 'allowedDomains', 'suggestedQuestions']);

        return view('dashboard.websites.show', [
            'website' => $website,
            'embedSnippet' => $website->embedSnippet(),
        ]);
    }

    public function edit(Request $request, Website $website): View
    {
        $this->ensureWebsiteInOrganization($request, $website);

        return view('dashboard.websites.edit-website', compact('website'));
    }

    public function editBot(Request $request, Website $website): View
    {
        $this->ensureWebsiteInOrganization($request, $website);
        $website->load('configuration');

        return view('dashboard.websites.edit-bot', compact('website'));
    }

    public function advanced(Request $request, Website $website): View
    {
        $this->ensureWebsiteInOrganization($request, $website);

        $website->load(['configuration', 'allowedDomains', 'suggestedQuestions', 'operatingHours']);

        return view('dashboard.websites.edit-advanced', compact('website'));
    }

    public function updateWebsite(Request $request, Website $website, WidgetConfigService $configService): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|url|max:2048',
            'category' => 'nullable|string|max:120',
            'language' => 'nullable|string|max:10',
            'contact_email' => 'nullable|email|max:255',
            'logo_url' => 'nullable|url|max:2048',
            'is_active' => 'boolean',
        ]);

        $website->update([
            'name' => $validated['name'],
            'url' => $validated['url'] ?? null,
            'category' => $validated['category'] ?? null,
            'language' => $validated['language'] ?? $website->language,
            'contact_email' => $validated['contact_email'] ?? null,
            'logo_url' => $validated['logo_url'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $configService->invalidate($website);

        return redirect()->route('websites.edit', $website)->with('success', 'Website information saved.');
    }

    public function updateBot(Request $request, Website $website, WidgetConfigService $configService): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $validated = $request->validate([
            'bot_name' => 'required|string|max:120',
            'welcome_message' => 'nullable|string|max:2000',
            'bot_description' => 'nullable|string|max:2000',
            'avatar_url' => 'nullable|url|max:2048',
            'locale' => 'required|string|max:10',
            'ai_tone' => 'nullable|string|max:64',
            'fallback_message' => 'nullable|string|max:2000',
            'primary_color' => 'required|string|max:20',
            'secondary_color' => 'required|string|max:20',
            'typing_animation' => 'boolean',
            'bot_online' => 'boolean',
            'ai_enabled' => 'boolean',
        ]);

        $website->configuration->update([
            'bot_name' => $validated['bot_name'],
            'bot_description' => $validated['bot_description'] ?? null,
            'avatar_url' => $validated['avatar_url'] ?? null,
            'locale' => $validated['locale'],
            'welcome_message' => $validated['welcome_message'] ?? null,
            'fallback_message' => $validated['fallback_message'] ?? null,
            'ai_tone' => $validated['ai_tone'] ?? 'professional',
            'primary_color' => $validated['primary_color'],
            'secondary_color' => $validated['secondary_color'],
            'typing_animation' => $request->boolean('typing_animation', true),
            'bot_online' => $request->boolean('bot_online', true),
            'ai_enabled' => $request->boolean('ai_enabled', true),
        ]);

        $configService->invalidate($website);

        return redirect()->route('websites.edit.bot', $website)->with('success', 'Bot settings saved.');
    }

    public function update(Request $request, Website $website, WidgetConfigService $configService): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|url|max:2048',
            'is_active' => 'boolean',
            'bot_name' => 'required|string|max:120',
            'avatar_url' => 'nullable|url|max:2048',
            'primary_color' => 'required|string|max:20',
            'secondary_color' => 'required|string|max:20',
            'theme_mode' => 'required|in:light,dark,auto',
            'position' => 'required|in:left,right',
            'widget_offset_bottom' => 'nullable|integer|min:0|max:200',
            'widget_offset_side' => 'nullable|integer|min:0|max:200',
            'hours' => 'nullable|array',
            'hours.*.opens_at' => 'nullable|date_format:H:i',
            'hours.*.closes_at' => 'nullable|date_format:H:i',
            'hours.*.is_closed' => 'boolean',
            'hours_timezone' => 'nullable|string|max:64',
            'locale' => 'required|string|max:10',
            'welcome_message' => 'nullable|string|max:2000',
            'typing_indicator_text' => 'nullable|string|max:120',
            'offline_message' => 'nullable|string|max:2000',
            'outside_hours_message' => 'nullable|string|max:2000',
            'ai_model' => 'required|string|max:64',
            'ai_temperature' => 'required|numeric|min:0|max:2',
            'confidence_threshold' => 'required|numeric|min:0|max:1',
            'system_prompt' => 'nullable|string|max:8000',
            'ai_enabled' => 'boolean',
            'custom_css' => 'nullable|string|max:50000',
            'custom_js' => 'nullable|string|max:50000',
            'rate_limit_per_minute' => 'required|integer|min:1|max:1000',
            'rate_limit_per_hour' => 'required|integer|min:1|max:100000',
            'require_domain_validation' => 'boolean',
            'modules' => 'nullable|array',
            'category' => 'nullable|string|max:120',
            'contact_email' => 'nullable|email|max:255',
            'language' => 'nullable|string|max:10',
            'logo_url' => 'nullable|url|max:2048',
            'bot_description' => 'nullable|string|max:2000',
            'fallback_message' => 'nullable|string|max:2000',
            'ai_tone' => 'nullable|string|max:64',
            'whatsapp_enabled' => 'boolean',
            'whatsapp_number' => 'nullable|string|max:40',
            'whatsapp_message' => 'nullable|string|max:500',
            'email_channel_enabled' => 'boolean',
            'support_email' => 'nullable|email|max:255',
            'email_subject' => 'nullable|string|max:255',
            'sound_enabled' => 'boolean',
        ]);

        $website->update([
            'name' => $validated['name'],
            'url' => $validated['url'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'category' => $validated['category'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'language' => $validated['language'] ?? $website->language,
            'logo_url' => $validated['logo_url'] ?? null,
        ]);

        $security = $website->configuration->security_settings ?? [];
        $security['widget_offset_bottom'] = (int) ($validated['widget_offset_bottom'] ?? $security['widget_offset_bottom'] ?? 24);
        $security['widget_offset_side'] = (int) ($validated['widget_offset_side'] ?? $security['widget_offset_side'] ?? 24);

        $website->configuration->update([
            'bot_name' => $validated['bot_name'],
            'bot_description' => $validated['bot_description'] ?? null,
            'fallback_message' => $validated['fallback_message'] ?? null,
            'ai_tone' => $validated['ai_tone'] ?? null,
            'avatar_url' => $validated['avatar_url'] ?? null,
            'primary_color' => $validated['primary_color'],
            'secondary_color' => $validated['secondary_color'],
            'theme_mode' => $validated['theme_mode'],
            'position' => $validated['position'],
            'locale' => $validated['locale'],
            'welcome_message' => $validated['welcome_message'] ?? null,
            'typing_indicator_text' => $validated['typing_indicator_text'] ?? 'Typing...',
            'offline_message' => $validated['offline_message'] ?? null,
            'outside_hours_message' => $validated['outside_hours_message'] ?? null,
            'ai_model' => $validated['ai_model'],
            'ai_temperature' => $validated['ai_temperature'],
            'confidence_threshold' => $validated['confidence_threshold'],
            'system_prompt' => $validated['system_prompt'] ?? null,
            'ai_enabled' => $request->boolean('ai_enabled'),
            'custom_css' => $validated['custom_css'] ?? null,
            'custom_js' => $validated['custom_js'] ?? null,
            'rate_limit_per_minute' => $validated['rate_limit_per_minute'],
            'rate_limit_per_hour' => $validated['rate_limit_per_hour'],
            'require_domain_validation' => $request->boolean('require_domain_validation'),
            'sound_enabled' => $request->boolean('sound_enabled', true),
            'enabled_modules' => $validated['modules'] ?? $website->configuration->modules(),
            'widget_channels' => [
                'whatsapp' => [
                    'enabled' => $request->boolean('whatsapp_enabled'),
                    'number' => $validated['whatsapp_number'] ?? '',
                    'message' => $validated['whatsapp_message'] ?? 'Hi, I need help from your website chatbot.',
                ],
                'email' => [
                    'enabled' => $request->boolean('email_channel_enabled'),
                    'address' => $validated['support_email'] ?? '',
                    'subject' => $validated['email_subject'] ?? 'Support request from chatbot',
                ],
            ],
            'security_settings' => $security,
        ]);

        $this->syncOperatingHours($website, $validated['hours'] ?? [], $validated['hours_timezone'] ?? null);

        $configService->invalidate($website);

        return redirect()->route('websites.advanced', $website)->with('success', 'Advanced settings saved. Changes are live instantly.');
    }

    /** @param array<int|string, array<string, mixed>> $hoursInput */
    protected function syncOperatingHours(Website $website, array $hoursInput, ?string $timezone): void
    {
        if ($hoursInput === []) {
            return;
        }

        $tz = $timezone ?: $website->operatingHours->first()?->timezone ?: config('app.timezone', 'UTC');

        foreach (range(0, 6) as $day) {
            $row = $hoursInput[$day] ?? $hoursInput[(string) $day] ?? null;
            if (! is_array($row)) {
                continue;
            }

            $record = $website->operatingHours()->firstOrCreate(['day_of_week' => $day]);
            $record->update([
                'opens_at' => $this->normalizeHourTime($row['opens_at'] ?? null, '09:00:00'),
                'closes_at' => $this->normalizeHourTime($row['closes_at'] ?? null, '17:00:00'),
                'is_closed' => ! empty($row['is_closed']),
                'timezone' => $tz,
            ]);
        }
    }

    protected function normalizeHourTime(?string $value, string $default): string
    {
        if (empty($value)) {
            return $default;
        }

        return strlen($value) === 5 ? $value.':00' : $value;
    }

    public function duplicate(Request $request, Website $website, WidgetConfigService $configService, WidgetPluginExportService $pluginExport): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $copy = Website::create([
            'organization_id' => $website->organization_id,
            'name' => $website->name.' (copy)',
            'url' => $website->url,
            'category' => $website->category,
            'contact_email' => $website->contact_email,
            'language' => $website->language,
            'logo_url' => $website->logo_url,
            'widget_enabled' => $website->widget_enabled,
        ]);

        $src = $website->configuration;
        $copy->configuration->update($src->only([
            'bot_name', 'bot_description', 'avatar_url', 'primary_color', 'secondary_color',
            'theme_mode', 'position', 'locale', 'welcome_message', 'typing_indicator_text',
            'offline_message', 'outside_hours_message', 'fallback_message', 'ai_tone',
            'typing_animation', 'bot_online',
            'ai_model', 'ai_temperature', 'confidence_threshold', 'system_prompt',
            'ai_enabled', 'custom_css', 'custom_js', 'enabled_modules', 'widget_channels',
            'rate_limit_per_minute', 'rate_limit_per_hour', 'require_domain_validation',
        ]));

        foreach ($website->qaPairs as $qa) {
            $copy->qaPairs()->create($qa->only([
                'question', 'answer', 'trigger_keywords', 'priority', 'category', 'tags',
                'is_active', 'is_published',
            ]));
        }

        foreach ($website->quickActions as $action) {
            $copy->quickActions()->create($action->only([
                'label', 'description', 'icon', 'color',
                'action_type', 'action_value', 'custom_answer',
                'sort_order', 'is_active',
            ]));
        }

        foreach ($website->triggerKeywords as $kw) {
            $copy->triggerKeywords()->create($kw->only(['keyword', 'action', 'response', 'is_active']));
        }

        $configService->invalidate($copy);
        $pluginExport->persistPackage($copy);

        return redirect()
            ->route('websites.embed', $copy)
            ->with('success', 'Website duplicated. New bot token generated — update embed on client sites.');
    }

    public function toggleStatus(Request $request, Website $website, WidgetConfigService $configService): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $website->update(['is_active' => ! $website->is_active]);
        $configService->invalidate($website);

        return back()->with('success', $website->is_active ? 'Chatbot activated.' : 'Chatbot paused.');
    }

    public function destroy(Request $request, Website $website, WidgetConfigService $configService): RedirectResponse
    {
        $this->ensureCanManageWebsites($request);
        $this->ensureWebsiteInOrganization($request, $website);

        $configService->invalidate($website);
        $website->delete();

        return redirect()->route('websites.index')->with('success', 'Website removed.');
    }
}
