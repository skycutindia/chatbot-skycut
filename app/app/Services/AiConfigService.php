<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiConfigService
{
    public function resolveApiKey(?Organization $organization = null): ?string
    {
        $settings = $organization?->settings ?? [];
        $orgKey = $this->normalizeKey($settings['openai_api_key'] ?? null);

        if ($orgKey !== null && ! empty($settings['use_org_openai_key'])) {
            return $orgKey;
        }

        $platform = $this->normalizeKey(PlatformSetting::get('openai_api_key'));
        if ($platform !== null) {
            return $platform;
        }

        $env = $this->normalizeKey(config('chatbot.openai.api_key'));
        if ($env !== null) {
            return $env;
        }

        // Saved org key without platform/env fallback (e.g. checkbox not enabled).
        return $orgKey;
    }

    /** Sync OPENAI_API_KEY from .env into platform_settings when DB has no platform key yet. */
    public function ensurePlatformKeyFromEnvironment(): void
    {
        if ($this->normalizeKey(PlatformSetting::get('openai_api_key')) !== null) {
            return;
        }

        $env = $this->normalizeKey(config('chatbot.openai.api_key'));
        if ($env !== null) {
            PlatformSetting::set('openai_api_key', $env);
        }
    }

    protected function normalizeKey(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' ? $value : null;
        }

        if (is_array($value) && isset($value[0]) && is_string($value[0])) {
            return $this->normalizeKey($value[0]);
        }

        return null;
    }

    public function resolveBaseUrl(): string
    {
        return rtrim(
            (string) (PlatformSetting::get('openai_base_url') ?: config('chatbot.openai.base_url')),
            '/'
        );
    }

    public function resolveDefaultModel(?Organization $organization = null): string
    {
        if ($organization) {
            $settings = $organization->settings ?? [];
            if (! empty($settings['openai_default_model'])) {
                return (string) $settings['openai_default_model'];
            }
        }

        return (string) (PlatformSetting::get('openai_default_model') ?: 'gpt-4o-mini');
    }

    public function maskApiKey(?string $key): ?string
    {
        if (! $key || strlen($key) < 12) {
            return $key ? '••••••••' : null;
        }

        return substr($key, 0, 7).'…'.substr($key, -4);
    }

    public function isConfigured(?Organization $organization = null): bool
    {
        return $this->resolveApiKey($organization) !== null;
    }

    /** @return array{ok: bool, message: string, model?: string} */
    public function testConnection(?Organization $organization = null): array
    {
        $apiKey = $this->resolveApiKey($organization);
        if (! $apiKey) {
            return ['ok' => false, 'message' => 'No API key configured.'];
        }

        try {
            $response = Http::timeout(20)
                ->withToken($apiKey)
                ->get($this->resolveBaseUrl().'/models');

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'message' => 'Connected to OpenAI successfully.',
                    'model' => $this->resolveDefaultModel($organization),
                ];
            }

            $error = $response->json('error.message') ?? $response->body();

            return ['ok' => false, 'message' => Str::limit((string) $error, 200)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /** @return array<string, bool> */
    public function defaultDashboardModules(): array
    {
        return [
            'websites' => true,
            'inbox' => true,
            'leads' => true,
            'reports' => true,
            'team' => true,
            'training' => true,
        ];
    }

    /** @return array<string, bool> */
    public function dashboardModules(?Organization $organization): array
    {
        $defaults = $this->defaultDashboardModules();
        if (! $organization) {
            return $defaults;
        }

        $stored = $organization->settings['dashboard_modules'] ?? [];

        foreach ($defaults as $key => $enabled) {
            if (array_key_exists($key, $stored)) {
                $defaults[$key] = (bool) $stored[$key];
            }
        }

        return $defaults;
    }

    public function isModuleEnabled(?Organization $organization, string $module): bool
    {
        $modules = $this->dashboardModules($organization);

        return $modules[$module] ?? true;
    }
}
