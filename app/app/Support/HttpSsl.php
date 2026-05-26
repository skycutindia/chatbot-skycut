<?php

namespace App\Support;

class HttpSsl
{
    /**
     * Guzzle / Laravel HTTP client options for TLS verification.
     *
     * @return array<string, bool|string>
     */
    public static function clientOptions(): array
    {
        $verify = config('chatbot.http.verify', true);

        if ($verify === false || $verify === 'false' || $verify === '0') {
            return ['verify' => false];
        }

        $bundle = config('chatbot.http.ca_bundle');
        if (is_string($bundle) && $bundle !== '' && is_file($bundle)) {
            return ['verify' => $bundle];
        }

        return ['verify' => true];
    }
}
