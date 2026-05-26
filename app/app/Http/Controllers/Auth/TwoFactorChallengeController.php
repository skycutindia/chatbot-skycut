<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\TwoFactorService;
use App\Support\PostLoginRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $userId = $request->session()->get('login.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(['login.id', 'login.remember']);

            return redirect()->route('login')->withErrors(['email' => 'Invalid session. Please sign in again.']);
        }

        $code = $request->string('code')->toString();
        $valid = $twoFactor->verifyCode($user, $code) || $twoFactor->verifyRecoveryCode($user, $code);

        if (! $valid) {
            return back()->withErrors(['code' => 'The provided code is invalid.']);
        }

        $remember = (bool) $request->session()->get('login.remember', false);
        $request->session()->forget(['login.id', 'login.remember']);

        Auth::login($user, $remember);
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
}
