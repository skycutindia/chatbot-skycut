<?php

namespace App\Services;

class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 20): string
    {
        $secret = '';
        $bytes = random_bytes($length);

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[ord($bytes[$i]) & 31];
        }

        return $secret;
    }

    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timestamp = time();

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->getCode($secret, $timestamp + ($offset * 30)), $code)) {
                return true;
            }
        }

        return false;
    }

    public function getCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $timeCounter = pack('N*', 0, intdiv($timestamp, 30));
        $key = $this->base32Decode($secret);
        $hash = hash_hmac('sha1', $timeCounter, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $otp = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1_000_000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    public function provisioningUri(string $secret, string $email, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer)
        );
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/\s+/', '', $secret) ?? '');
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0, $len = strlen($secret); $i < $len; $i++) {
            $value = strpos(self::BASE32_ALPHABET, $secret[$i]);

            if ($value === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
