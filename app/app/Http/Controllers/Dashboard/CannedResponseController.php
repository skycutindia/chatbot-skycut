<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CannedResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CannedResponseController extends Controller
{
    public function index(Request $request): View
    {
        $responses = CannedResponse::query()
            ->where('organization_id', $request->user()->organization_id)
            ->with('website')
            ->latest()
            ->paginate(25);

        return view('dashboard.canned-responses.index', compact('responses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'shortcut' => 'nullable|string|max:32',
            'body' => 'required|string|max:4000',
            'website_id' => 'nullable|exists:websites,id',
        ]);

        if ($validated['website_id'] ?? null) {
            abort_unless(
                \App\Models\Website::where('id', $validated['website_id'])
                    ->where('organization_id', $request->user()->organization_id)
                    ->exists(),
                403
            );
        }

        CannedResponse::create([
            'organization_id' => $request->user()->organization_id,
            'website_id' => $validated['website_id'] ?? null,
            'title' => $validated['title'],
            'shortcut' => $validated['shortcut'] ?? null,
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Canned response saved.');
    }

    public function destroy(CannedResponse $cannedResponse): RedirectResponse
    {
        abort_unless($cannedResponse->organization_id === auth()->user()->organization_id, 403);
        $cannedResponse->delete();

        return back()->with('success', 'Canned response removed.');
    }
}
