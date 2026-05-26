<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function show(Request $request, TwoFactorService $twoFactor): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return view('dashboard.settings.two-factor-manage', compact('user'));
        }

        if ($user->hasPendingTwoFactorSetup() && $user->two_factor_secret) {
            $secret = $user->two_factor_secret;
            $qrUri = $twoFactor->provisioningUri($user, $secret);

            return view('dashboard.settings.two-factor-setup', compact('user', 'secret', 'qrUri'));
        }

        return view('dashboard.settings.two-factor', compact('user'));
    }

    public function enable(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate(['password' => 'required|current_password']);

        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('settings.two-factor.show');
        }

        $twoFactor->beginSetup($user);

        return redirect()->route('settings.two-factor.show')
            ->with('success', 'Scan the QR code with your authenticator app, then enter the 6-digit code.');
    }

    public function confirm(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);

        $user = $request->user();
        $code = preg_replace('/\D/', '', $request->string('code')->toString()) ?? '';

        if (strlen($code) !== 6) {
            return back()->withErrors(['code' => 'Enter a valid 6-digit code.']);
        }

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('settings.two-factor.show');
        }

        if (! $twoFactor->confirmSetup($user, $code)) {
            return back()->withErrors(['code' => 'Invalid authentication code. Try again.']);
        }

        $user->refresh();

        return redirect()->route('settings.two-factor.recovery-codes')
            ->with('recovery_codes', $user->two_factor_recovery_codes);
    }

    public function recoveryCodes(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('settings.two-factor.show');
        }

        $codes = session('recovery_codes');

        return view('dashboard.settings.two-factor-recovery-codes', compact('user', 'codes'));
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate(['password' => 'required|current_password']);

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('settings.two-factor.show');
        }

        $codes = $twoFactor->regenerateRecoveryCodes($user);

        return redirect()->route('settings.two-factor.recovery-codes')
            ->with('recovery_codes', $codes)
            ->with('success', 'New recovery codes generated. Store them securely.');
    }

    public function disable(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate([
            'password' => 'required|current_password',
            'code' => 'required|string',
        ]);

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('settings.two-factor.show');
        }

        $code = preg_replace('/\D/', '', $request->string('code')->toString()) ?? '';

        if (! $twoFactor->verifyCode($user, $code)) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        $twoFactor->disable($user);

        return redirect()->route('settings.two-factor.show')
            ->with('success', 'Two-factor authentication has been disabled.');
    }

    public function cancelSetup(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasPendingTwoFactorSetup()) {
            $twoFactor->disable($user);
        }

        return redirect()->route('settings.two-factor.show');
    }
}
