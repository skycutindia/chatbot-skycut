<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class TwoFactorService
{
    public function __construct(
        private TotpService $totp,
    ) {}

    public function beginSetup(User $user): string
    {
        $secret = $this->totp->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return $secret;
    }

    public function confirmSetup(User $user, string $code): bool
    {
        $secret = $user->two_factor_secret;

        if (! $secret || ! $this->totp->verify($secret, $code)) {
            return false;
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
        ])->save();

        return true;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    public function verifyCode(User $user, string $code): bool
    {
        $secret = $user->two_factor_secret;

        if (! $secret) {
            return false;
        }

        return $this->totp->verify($secret, $code);
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $normalized = strtoupper(str_replace([' ', '-'], '', $code));
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $stored) {
            $storedNormalized = strtoupper(str_replace('-', '', $stored));

            if (hash_equals($storedNormalized, $normalized)) {
                unset($codes[$index]);
                $user->forceFill([
                    'two_factor_recovery_codes' => array_values($codes),
                ])->save();

                return true;
            }
        }

        return false;
    }

    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return $codes;
    }

    public function provisioningUri(User $user, string $secret): string
    {
        return $this->totp->provisioningUri(
            $secret,
            $user->email,
            config('app.name', 'AI Chatbot Hub Pro')
        );
    }

    /** @return list<string> */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4)))
            ->all();
    }
}
