<?php

namespace App\Http\Controllers;

use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TeamInviteAcceptController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $invite = $this->resolvePending($token);

        if (! $invite) {
            return view('auth.invite-invalid');
        }

        $existing = User::query()->where('email', $invite->email)->first();

        if (Auth::check()) {
            if (strtolower(Auth::user()->email) !== $invite->email) {
                return view('auth.invite-invalid', [
                    'message' => 'You are signed in as a different user. Sign out and open the invite link again.',
                ]);
            }

            return $this->acceptInvite($invite, Auth::user());
        }

        return view('auth.invite-accept', [
            'invite' => $invite,
            'organization' => $invite->organization,
            'needsAccount' => ! $existing,
            'existingName' => $existing?->name,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invite = $this->resolvePending($token);

        if (! $invite) {
            return redirect()->route('login')->withErrors(['email' => 'This invite is invalid or has expired.']);
        }

        $existing = User::query()->where('email', $invite->email)->first();

        if ($existing && Auth::check() && Auth::id() !== $existing->id) {
            return back()->withErrors(['email' => 'Sign in with the invited email to continue.']);
        }

        if ($existing) {
            if (! Auth::check()) {
                $request->validate(['password' => 'required|string']);

                if (! Auth::attempt(['email' => $invite->email, 'password' => $request->password], true)) {
                    return back()->withErrors(['password' => 'Incorrect password.'])->withInput();
                }
            }

            return $this->acceptInvite($invite, $existing);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'organization_id' => $invite->organization_id,
            'name' => $validated['name'],
            'email' => $invite->email,
            'password' => Hash::make($validated['password']),
            'role' => $invite->role,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        return $this->acceptInvite($invite, $user);
    }

    protected function resolvePending(string $token): ?OrganizationInvite
    {
        $invite = OrganizationInvite::query()
            ->with('organization')
            ->where('token', $token)
            ->first();

        if (! $invite || ! $invite->isPending()) {
            return null;
        }

        return $invite;
    }

    protected function acceptInvite(OrganizationInvite $invite, User $user): RedirectResponse
    {
        if (strtolower($user->email) !== $invite->email) {
            abort(403);
        }

        if ($user->organization_id && $user->organization_id !== $invite->organization_id) {
            return redirect()->route('dashboard')->withErrors([
                'email' => 'Your account already belongs to another organization.',
            ]);
        }

        $user->update([
            'organization_id' => $invite->organization_id,
            'role' => $invite->role,
            'is_active' => true,
        ]);

        $invite->update(['accepted_at' => now()]);

        return redirect()->route('dashboard')->with('success', 'Welcome to '.$invite->organization->name.'!');
    }
}
