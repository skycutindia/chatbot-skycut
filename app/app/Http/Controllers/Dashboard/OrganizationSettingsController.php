<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\AuthorizesTenantRole;
use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use App\Models\OrganizationInvite;
use App\Support\RolePermissionMatrix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationSettingsController extends Controller
{
    use AuthorizesTenantRole;

    public function edit(Request $request): View
    {
        $this->ensureCanManageOrganization($request);

        $organization = $request->user()->organization;

        abort_unless($organization, 404);

        $pendingInvites = OrganizationInvite::query()
            ->where('organization_id', $organization->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        return view('dashboard.settings.organization', [
            'organization' => $organization,
            'roleMatrix' => RolePermissionMatrix::rows(),
            'pendingInvites' => $pendingInvites,
            'inviteRoles' => $this->assignableInviteRoles($request),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureCanManageOrganization($request);

        $organization = $request->user()->organization;

        abort_unless($organization, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo_url' => 'nullable|url|max:2048',
            'timezone' => 'required|string|max:64',
            'notification_email' => 'nullable|email|max:255',
            'slack_webhook_url' => 'nullable|url|max:2048',
            'teams_webhook_url' => 'nullable|url|max:2048',
        ]);

        $settings = $organization->settings ?? [];
        $settings['notification_email'] = $validated['notification_email'] ?? null;
        $settings['slack_webhook_url'] = $validated['slack_webhook_url'] ?? null;
        $settings['teams_webhook_url'] = $validated['teams_webhook_url'] ?? null;
        $settings['notify_slack_handoff'] = $request->boolean('notify_slack_handoff');
        $settings['notify_slack_new_message'] = $request->boolean('notify_slack_new_message');
        $settings['notify_teams_handoff'] = $request->boolean('notify_teams_handoff');
        $settings['notify_teams_new_message'] = $request->boolean('notify_teams_new_message');

        $organization->update([
            'name' => $validated['name'],
            'logo_url' => $validated['logo_url'] ?? null,
            'timezone' => $validated['timezone'],
            'settings' => $settings,
        ]);

        return back()->with('success', 'Organization settings saved.');
    }

    /** @return list<string> */
    protected function assignableInviteRoles(Request $request): array
    {
        $roles = UserRole::tenantRoles();

        if ($request->user()->roleEnum() !== UserRole::Owner) {
            $roles = array_values(array_filter($roles, fn (string $r) => $r !== UserRole::Owner->value));
        }

        return $roles;
    }
}
