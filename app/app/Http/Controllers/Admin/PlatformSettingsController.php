<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'openaiApiKey' => PlatformSetting::get('openai_api_key', config('chatbot.openai.api_key')),
            'openaiModel' => PlatformSetting::get('openai_default_model', 'gpt-4o-mini'),
            'maintenanceMode' => (bool) PlatformSetting::get('maintenance_mode', false),
            'platformName' => PlatformSetting::get('platform_name', config('app.name')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_name' => 'required|string|max:120',
            'openai_api_key' => 'nullable|string|max:255',
            'openai_default_model' => 'required|string|max:64',
            'maintenance_mode' => 'boolean',
        ]);

        PlatformSetting::set('platform_name', $validated['platform_name']);
        PlatformSetting::set('openai_default_model', $validated['openai_default_model']);
        PlatformSetting::set('maintenance_mode', $request->boolean('maintenance_mode'));

        if ($request->filled('openai_api_key')) {
            PlatformSetting::set('openai_api_key', $validated['openai_api_key']);
        }

        return back()->with('success', 'Platform settings saved.');
    }
}
