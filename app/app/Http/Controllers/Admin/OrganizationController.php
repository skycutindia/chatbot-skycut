<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(): View
    {
        $organizations = Organization::withCount(['websites', 'users', 'leads'])
            ->latest()
            ->paginate(20);

        return view('admin.organizations.index', compact('organizations'));
    }

    public function show(Organization $organization): View
    {
        $organization->load(['websites.configuration', 'users', 'leads' => fn ($q) => $q->latest()->limit(10)]);

        return view('admin.organizations.show', compact('organization'));
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'timezone' => 'nullable|string|max:64',
        ]);

        $organization->update([
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
            'timezone' => $validated['timezone'] ?? $organization->timezone,
        ]);

        return back()->with('success', 'Organization updated.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $organization->delete();

        return redirect()->route('admin.organizations.index')->with('success', 'Organization removed.');
    }
}
