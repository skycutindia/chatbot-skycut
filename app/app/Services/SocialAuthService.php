<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\LoginHistory;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use App\Support\PostLoginRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthService
{
    /** @return list<string> */
    public function providers(): array
    {
        return config('chatbot.social.providers', ['google', 'github']);
    }

    public function isEnabled(string $provider): bool
    {
        if (! in_array($provider, $this->providers(), true)) {
            return false;
        }

        return (bool) config("services.{$provider}.client_id")
            && (bool) config("services.{$provider}.client_secret");
    }

    /** @return list<string> */
    public function enabledProviders(): array
    {
        return array_values(array_filter($this->providers(), fn (string $p) => $this->isEnabled($p)));
    }

    public function findOrCreateUser(string $provider, SocialiteUser $oauthUser): User
    {
        $account = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', (string) $oauthUser->getId())
            ->first();

        if ($account) {
            $this->syncAccount($account, $oauthUser);

            return $account->user;
        }

        $email = $oauthUser->getEmail();

        if ($email) {
            $existing = User::where('email', $email)->first();

            if ($existing) {
                $this->linkAccount($existing, $provider, $oauthUser);

                return $existing->fresh();
            }
        }

        return $this->registerUser($provider, $oauthUser, $email);
    }

    public function loginUser(User $user, Request $request, bool $remember = true): RedirectResponse
    {
        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated.',
            ]);
        }

        if (! $user->allowsIp($request->ip())) {
            return redirect()->route('login')->withErrors([
                'email' => 'Login not allowed from this IP address.',
            ]);
        }

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $remember);

            return redirect()->route('two-factor.login');
        }

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

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(PostLoginRedirect::url($user));
    }

    protected function registerUser(string $provider, SocialiteUser $oauthUser, ?string $email): User
    {
        if (! $email) {
            throw new \RuntimeException('Your '.$provider.' account did not share an email address. Use email registration instead.');
        }

        $displayName = $oauthUser->getName() ?: Str::before($email, '@');
        $companyName = $displayName."'s Workspace";

        $org = Organization::create([
            'name' => $companyName,
            'slug' => Str::slug($companyName).'-'.Str::lower(Str::random(4)),
            'is_active' => true,
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => $displayName,
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'email_verified_at' => now(),
            'avatar_url' => $oauthUser->getAvatar(),
            'role' => UserRole::Owner->value,
            'is_active' => true,
        ]);

        $this->linkAccount($user, $provider, $oauthUser);

        return $user;
    }

    protected function linkAccount(User $user, string $provider, SocialiteUser $oauthUser): SocialAccount
    {
        if (! $user->avatar_url && $oauthUser->getAvatar()) {
            $user->update(['avatar_url' => $oauthUser->getAvatar()]);
        }

        if (! $user->hasVerifiedEmail() && $oauthUser->getEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return SocialAccount::updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => (string) $oauthUser->getId(),
            ],
            [
                'user_id' => $user->id,
                'provider_email' => $oauthUser->getEmail(),
                'avatar_url' => $oauthUser->getAvatar(),
            ]
        );
    }

    protected function syncAccount(SocialAccount $account, SocialiteUser $oauthUser): void
    {
        $account->update([
            'provider_email' => $oauthUser->getEmail(),
            'avatar_url' => $oauthUser->getAvatar(),
        ]);
    }
}
