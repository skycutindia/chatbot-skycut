<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\AuthorizesTenantRole;
use App\Http\Controllers\Controller;
use App\Mail\OrganizationInviteMail;
use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class OrganizationInviteController extends Controller
{
    use AuthorizesTenantRole;

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManageOrganization($request);

        $organization = $request->user()->organization;
        abort_unless($organization, 404);

        $roles = $this->assignableRoles($request);

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role' => ['required', Rule::in($roles)],
        ]);

        $email = strtolower($validated['email']);

        if (User::query()->where('email', $email)->where('organization_id', $organization->id)->exists()) {
            return back()->withErrors(['email' => 'This user is already on your team.'])->withInput();
        }

        if (User::query()->where('email', $email)->whereNotNull('organization_id')->where('organization_id', '!=', $organization->id)->exists()) {
            return back()->withErrors(['email' => 'This email belongs to another organization.'])->withInput();
        }

        OrganizationInvite::query()
            ->where('organization_id', $organization->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->delete();

        $invite = OrganizationInvite::create([
            'organization_id' => $organization->id,
            'invited_by' => $request->user()->id,
            'email' => $email,
            'role' => $validated['role'],
            'token' => OrganizationInvite::generateToken(),
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($email)->send(new OrganizationInviteMail($invite));

        return back()->with('success', 'Invite sent to '.$email.'. They can use the accept link or the email we sent.');
    }

    public function destroy(Request $request, OrganizationInvite $invite): RedirectResponse
    {
        $this->ensureCanManageOrganization($request);

        abort_unless($invite->organization_id === $request->user()->organization_id, 404);
        abort_if($invite->accepted_at !== null, 404);

        $invite->delete();

        return back()->with('success', 'Invite cancelled.');
    }

    /** @return list<string> */
    protected function assignableRoles(Request $request): array
    {
        $roles = UserRole::tenantRoles();

        if ($request->user()->roleEnum() !== UserRole::Owner) {
            $roles = array_values(array_filter($roles, fn (string $r) => $r !== UserRole::Owner->value));
        }

        return $roles;
    }
}
