<?php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    'widget' => [
        'cache_ttl' => (int) env('WIDGET_CONFIG_CACHE_TTL', 60),
        'cors_max_age' => 86400,
    ],

    'lockout_attempts' => (int) env('AUTH_LOCKOUT_ATTEMPTS', 5),
    'lockout_minutes' => (int) env('AUTH_LOCKOUT_MINUTES', 30),
    'handoff_low_confidence_streak' => (int) env('CHAT_HANDOFF_STREAK', 2),
    'sla_minutes' => (int) env('CHAT_SLA_MINUTES', 15),

    'attachments' => [
        'max_bytes' => (int) env('CHAT_ATTACHMENT_MAX_BYTES', 10 * 1024 * 1024),
    ],

    'pwa' => [
        'name' => env('AGENT_PWA_NAME', 'SkyCut Live Agent'),
        'short_name' => env('AGENT_PWA_SHORT_NAME', 'Live Agent'),
        'description' => 'Mobile agent console for live chat',
        'theme_color' => env('AGENT_PWA_THEME', '#2563eb'),
        'background_color' => env('AGENT_PWA_BG', '#0f172a'),
        'vapid_public_key' => env('VAPID_PUBLIC_KEY', ''),
    ],

    'http' => [
        // Use bundled Mozilla CA on Windows/local when system store is missing (cURL error 60).
        'verify' => env('HTTP_SSL_VERIFY', true),
        'ca_bundle' => env('HTTP_CA_BUNDLE', storage_path('certs/cacert.pem')),
    ],

    'crawl' => [
        'max_pages' => (int) env('CRAWL_MAX_PAGES', 30),
        'max_depth' => (int) env('CRAWL_MAX_DEPTH', 2),
        'timeout' => (int) env('CRAWL_TIMEOUT', 15),
    ],

    'default_handoff_triggers' => [
        'keywords' => ['human', 'agent', 'live chat', 'speak to someone', 'real person', 'representative'],
    ],

    'default_system_prompt' => 'You are a helpful customer support assistant for AI Chatbot Hub Pro. Answer based on the knowledge base when possible. Be concise, friendly, and professional. If you cannot help, suggest connecting with a live agent.',

    'semantic_search' => [
        'enabled' => (bool) env('SEMANTIC_SEARCH_ENABLED', false),
        'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'min_score' => (float) env('SEMANTIC_SEARCH_MIN_SCORE', 0.72),
    ],

    'social' => [
        'providers' => ['google', 'github'],
    ],

    'demo_website_slug' => env('DEMO_WEBSITE_SLUG', 'skycut'),

    'whatsapp' => [
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v21.0'),
    ],

    'reactions' => [
        'emojis' => ['👍', '❤️', '😂', '😮', '🙏'],
    ],
];
