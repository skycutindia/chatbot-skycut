<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $members = User::query()
            ->where('organization_id', $request->user()->organization_id)
            ->latest()
            ->paginate(20);

        return view('dashboard.team.index', [
            'members' => $members,
            'roles' => UserRole::tenantRoles(),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.team.create', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in($this->assignableRoles())],
        ]);

        User::create([
            'organization_id' => $request->user()->organization_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return redirect()->route('team.index')->with('success', 'Team member added.');
    }

    public function edit(User $user): View
    {
        $this->authorizeMember($user);

        return view('dashboard.team.edit', [
            'member' => $user,
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeMember($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in($this->assignableRoles())],
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('team.index')->with('success', 'Team member updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeMember($user);
        abort_if($user->id === auth()->id(), 403, 'You cannot remove yourself.');

        $user->delete();

        return redirect()->route('team.index')->with('success', 'Team member removed.');
    }

    /** @return list<string> */
    protected function assignableRoles(): array
    {
        $roles = UserRole::tenantRoles();

        if (auth()->user()->roleEnum() !== UserRole::Owner) {
            $roles = array_values(array_filter($roles, fn (string $r) => $r !== UserRole::Owner->value));
        }

        return $roles;
    }

    protected function authorizeMember(User $user): void
    {
        abort_unless($user->organization_id === auth()->user()->organization_id, 403);
    }
}
