<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta Platform (Facebook, Instagram, WhatsApp)
    |--------------------------------------------------------------------------
    */
    'meta' => [
        'app_id' => env('META_APP_ID', ''),
        'app_secret' => env('META_APP_SECRET', ''),
        'instagram_app_id' => env('META_INSTAGRAM_APP_ID', env('META_APP_ID', '')),
        'instagram_app_secret' => env('META_INSTAGRAM_APP_SECRET', env('META_APP_SECRET', '')),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN', ''),
        'graph_api_version' => env('META_GRAPH_API_VERSION', 'v21.0'),
        'login_config_id' => env('META_LOGIN_CONFIG_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'scoring_model' => env('GEMINI_SCORING_MODEL', 'gemini-2.0-flash-lite'),
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'), // gemini, ollama, claude, openai
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model'    => env('OLLAMA_MODEL', 'qwen2.5:7b'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Evolution API (WhatsApp QR Gateway)
    |--------------------------------------------------------------------------
    | Self-hosted Evolution API instance running on the same server.
    | Used for connecting WhatsApp Business numbers without Meta setup.
    |
    | WHAT TO EDIT IF SOMETHING BREAKS:
    |   - API version changed  → update EVOLUTION_API_URL to new base path
    |   - Auth header changed  → update EvolutionApiService::headers()
    |   - Endpoint paths changed → update EvolutionApiService method URLs
    |   - Webhook payload changed → update EvolutionWebhookController::handle()
    |
    | Docs: /docs/whatsapp-gateway.md
    */
    /*
    |--------------------------------------------------------------------------
    | TikTok Business Messaging
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Snapchat
    |--------------------------------------------------------------------------
    */
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect'      => '/auth/google/callback',
    ],

    'snapchat' => [
        // Marketing API OAuth 2.0 (Public Profile Messaging API)
        // Register at: Snap Business Manager → Business Details → Marketing API
        'marketing_client_id'     => env('SNAPCHAT_MARKETING_CLIENT_ID', ''),
        'marketing_client_secret' => env('SNAPCHAT_MARKETING_CLIENT_SECRET', ''),
        'redirect'                => env('SNAPCHAT_REDIRECT_URI', ''), // Must match redirect URI registered in Business Manager
        'webhook_secret'          => env('SNAPCHAT_WEBHOOK_SECRET', ''),
        // Legacy Snap Kit credentials (no longer used for primary flow)
        'client_id'               => env('SNAPCHAT_CLIENT_ID', ''),
        'public_client_id'        => env('SNAPCHAT_PUBLIC_CLIENT_ID', ''),
        'client_secret'           => env('SNAPCHAT_CLIENT_SECRET', ''),
    ],

    'tiktok' => [
        'app_id'         => env('TIKTOK_APP_ID'),
        'client_key'     => env('TIKTOK_CLIENT_KEY', ''),
        'client_secret'  => env('TIKTOK_CLIENT_SECRET', ''),
        'webhook_secret' => env('TIKTOK_WEBHOOK_SECRET', ''),
    ],

    'evolution' => [
        'url'        => env('EVOLUTION_API_URL', 'http://localhost:8080'),
        'api_key'    => env('EVOLUTION_API_KEY', ''),
        'webhook_url'  => env('EVOLUTION_WEBHOOK_URL', ''),  // public URL Laravel receives webhooks on
        'webhook_host' => env('EVOLUTION_WEBHOOK_HOST', ''), // optional Host header override (for local dev behind a reverse proxy)
    ],

];
