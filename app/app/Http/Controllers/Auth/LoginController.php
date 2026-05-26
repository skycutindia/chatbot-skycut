<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use App\Support\DemoWebsiteRedirect;
use App\Support\PostLoginRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->isLocked()) {
            return back()->withErrors(['email' => 'Account is temporarily locked. Try again later.'])->onlyInput('email');
        }

        if ($user && ! $user->allowsIp($request->ip())) {
            return back()->withErrors(['email' => 'Login not allowed from this IP address.'])->onlyInput('email');
        }

        if ($user && ! $user->is_active) {
            return back()->withErrors(['email' => 'This account has been deactivated.'])->onlyInput('email');
        }

        if (Auth::attempt($credentials, false)) {
            $user = $request->user();

            if ($user->hasTwoFactorEnabled()) {
                Auth::logout();

                $request->session()->put('login.id', $user->id);
                $request->session()->put('login.remember', false);

                return redirect()->route('two-factor.login');
            }

            $request->session()->regenerate();
            $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);

            LoginHistory::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'success',
                'logged_in_at' => now(),
            ]);

            return redirect()->intended(PostLoginRedirect::url($user));
        }

        if ($user) {
            $attempts = $user->failed_login_attempts + 1;
            $updates = ['failed_login_attempts' => $attempts];
            if ($attempts >= (int) config('chatbot.lockout_attempts', 5)) {
                $updates['locked_until'] = now()->addMinutes((int) config('chatbot.lockout_minutes', 30));
                $updates['failed_login_attempts'] = 0;
            }
            $user->update($updates);

            LoginHistory::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
                'logged_in_at' => now(),
            ]);
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logoutUser($request);

        return DemoWebsiteRedirect::redirect();
    }

    public function sessionExpired(Request $request): RedirectResponse
    {
        $this->logoutUser($request);

        return DemoWebsiteRedirect::redirect();
    }

    public function beaconLogout(Request $request): Response
    {
        if ($request->user()) {
            $this->logoutUser($request);
        }

        return response()->noContent();
    }

    protected function logoutUser(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
