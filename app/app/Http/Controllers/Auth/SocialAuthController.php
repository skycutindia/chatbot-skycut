<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider, SocialAuthService $socialAuth): RedirectResponse
    {
        if (! $socialAuth->isEnabled($provider)) {
            return redirect()->route('login')->withErrors([
                'email' => ucfirst($provider).' sign-in is not configured.',
            ]);
        }

        $driver = Socialite::driver($provider);

        if ($provider === 'github') {
            $driver = $driver->scopes(['user:email']);
        }

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider, SocialAuthService $socialAuth): RedirectResponse
    {
        if (! $socialAuth->isEnabled($provider)) {
            return redirect()->route('login')->withErrors([
                'email' => ucfirst($provider).' sign-in is not configured.',
            ]);
        }

        try {
            $oauthUser = Socialite::driver($provider)->user();
            $user = $socialAuth->findOrCreateUser($provider, $oauthUser);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'email' => $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : 'Social sign-in failed. Please try again.',
            ]);
        }

        return $socialAuth->loginUser($user, $request);
    }
}
