<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Models\WhatsappChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsAppSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $organization = $request->user()->organization;
        abort_unless($organization, 404);

        $channel = WhatsappChannel::query()->where('organization_id', $organization->id)->first();
        $websites = Website::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $webhookUrl = route('api.whatsapp.webhook', $organization->slug);

        return view('dashboard.settings.whatsapp', compact('organization', 'channel', 'websites', 'webhookUrl'));
    }

    public function update(Request $request): RedirectResponse
    {
        $organization = $request->user()->organization;
        abort_unless($organization, 404);

        $validated = $request->validate([
            'phone_number_id' => 'required|string|max:64',
            'display_phone' => 'nullable|string|max:32',
            'access_token' => 'nullable|string|max:4096',
            'website_id' => 'nullable|exists:websites,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $channel = WhatsappChannel::query()->firstOrNew(['organization_id' => $organization->id]);

        if (! $channel->exists && empty($validated['access_token'])) {
            return back()->withErrors(['access_token' => 'Access token is required.'])->withInput();
        }

        if (! empty($validated['website_id'])) {
            abort_unless(
                Website::where('id', $validated['website_id'])
                    ->where('organization_id', $organization->id)
                    ->exists(),
                403
            );
        }

        if (! $channel->exists) {
            $channel->verify_token = Str::random(32);
        }

        $channel->fill([
            'phone_number_id' => $validated['phone_number_id'],
            'display_phone' => $validated['display_phone'] ?? null,
            'website_id' => $validated['website_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! empty($validated['access_token'])) {
            $channel->access_token = $validated['access_token'];
        }

        $channel->save();

        return back()->with('success', 'WhatsApp channel saved.');
    }
}
