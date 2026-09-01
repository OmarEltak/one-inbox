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
        'app_secret_legacy' => env('META_APP_SECRET_LEGACY', ''),
        'instagram_app_id' => env('META_INSTAGRAM_APP_ID', env('META_APP_ID', '')),
        'instagram_app_secret' => env('META_INSTAGRAM_APP_SECRET', env('META_APP_SECRET', '')),
        'instagram_app_secret_legacy' => env('META_INSTAGRAM_APP_SECRET_LEGACY', ''),
        // Set true once the Meta App has Advanced Access approved by Meta. While false,
        // the customer-facing Connections page routes Facebook/Instagram through the
        // managed onboarding flow (admin connects on the customer's behalf and
        // re-assigns the page) instead of direct OAuth.
        'app_verified' => env('META_APP_VERIFIED', false),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN', ''),
        'graph_api_version' => env('META_GRAPH_API_VERSION', 'v21.0'),
        'login_config_id' => env('META_LOGIN_CONFIG_ID', ''),
        // Full-auto managed onboarding: when true, new OnboardingRequests are
        // processed by App\Services\Meta\OnboardingAutomator — parses the page
        // URL, refreshes super-admin's /me/accounts list, asks the AI whether
        // the submitted business name matches, then auto-assigns or auto-rejects
        // with an AI-drafted reason. Kill switch for the whole pipeline.
        'managed_onboarding_auto' => env('MANAGED_ONBOARDING_AUTO', false),
        'managed_onboarding_notify' => env('MANAGED_ONBOARDING_NOTIFY_EMAIL', 'omareltak7@gmail.com'),
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
        // 2.5 series. 2.0 was retired from the free tier and now requires billing
        // on most new projects; 2.5 Flash still has a usable free-tier quota.
        // Both reply and scoring use the same model for now — switch scoring back
        // to gemini-2.5-flash-lite once on paid tier for cheaper high-volume scoring.
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'scoring_model' => env('GEMINI_SCORING_MODEL', 'gemini-2.5-flash'),
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'), // gemini, ollama, nararouter, claude, openai
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model'    => env('OLLAMA_MODEL', 'qwen2.5:7b'),
    ],

    // NaraRouter — OpenAI-compatible chat completions router. Nara's Free plan
    // gives access to a curated model set with per-model quota weights (0.05x
    // ... 3x). Chain order: best available quality → mistral-large as the
    // reliable final safety net (verified to never disconnect from Nara).
    //
    // Multi-key: NARAROUTER_API_KEY is primary, NARAROUTER_API_KEY_SECONDARY
    // is optional. When primary hits 401/402/429 (auth/quota/rate limit) we
    // rotate to secondary for the SAME model attempt. Both exhausted for one
    // model → cascade to next model, restart key rotation. Both exhausted for
    // every model in the chain → email alert to NARAROUTER_ALERT_EMAIL.
    'nararouter' => [
        'api_key'           => env('NARAROUTER_API_KEY'),                                          // primary (BC)
        'api_key_secondary' => env('NARAROUTER_API_KEY_SECONDARY'),                                // optional 2nd account
        'base_url'          => env('NARAROUTER_BASE_URL', 'https://router.bynara.id/v1'),
        'model'             => env('NARAROUTER_MODEL', 'agnes-2.5-flash'),
        'scoring_model'     => env('NARAROUTER_SCORING_MODEL', 'ox-alpha-bynara'),                 // 0.05x weight — near-zero quota drain for background scoring/analysis
        'fallback_models'   => env(
            'NARAROUTER_FALLBACK_MODELS',
            'agnes-2.5-flash,agnes-2.0-flash,nemotron-3-ultra,qwen-3.8-max-free,deepseek-v4-flash,mistral-large'
        ),
        'reset_hours'       => (int) env('NARAROUTER_RESET_HOURS', 5),                             // return to head-of-chain every N hours from FIRST fallback
        'alert_email'       => env('NARAROUTER_ALERT_EMAIL', 'omareltak7@gmail.com'),              // notified when every (model × key) attempt failed
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
        'client_id'         => env('GOOGLE_CLIENT_ID', ''),
        'client_secret'     => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect'          => '/auth/google/callback',
        'site_verification' => env('GOOGLE_SITE_VERIFICATION', ''),
    ],

    'bing' => [
        // Bing Webmaster Tools verification meta tag content.
        // Get from bing.com/webmasters → Add site → HTML meta tag method.
        'site_verification' => env('BING_SITE_VERIFICATION', ''),
    ],

    'ahrefs' => [
        // Ahrefs Webmaster Tools verification meta tag content.
        // Get from ahrefs.com/webmaster-tools → Verify site → HTML tag method.
        'site_verification' => env('AHREFS_SITE_VERIFICATION', ''),
    ],

    'clarity' => [
        // Microsoft Clarity project ID (found in Clarity dashboard → Settings → Setup).
        // Free session-recording + heatmap tool. Leave blank to disable in dev.
        'project_id' => env('CLARITY_PROJECT_ID', ''),
    ],

    'heronsignal' => [
        // Public tracker key from HeronSignal dashboard → Install.
        // Loads real user monitoring: sessions, frontend errors, failed requests,
        // funnels, custom events. Leave blank to disable (dev/local).
        // Docs: https://heronsignal.com/llms.txt
        'public_key'  => env('HERONSIGNAL_PUBLIC_KEY', ''),
        'service'     => env('HERONSIGNAL_SERVICE', 'ot1-pro-web'),
        'environment' => env('HERONSIGNAL_ENV', env('APP_ENV', 'production')),
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

    /*
    |--------------------------------------------------------------------------
    | Wuzapi (WhatsApp QR gateway, whatsmeow-based)
    |--------------------------------------------------------------------------
    | The active QR-based WhatsApp gateway. Whatsmeow tracks the WA mobile
    | protocol much more closely than Baileys, which is why this replaced
    | the old Evolution API setup. Container started via:
    |   docker compose -f docker-compose.wuzapi.yml up -d
    |
    | EvolutionApiService now routes here behind the scenes; the class will
    | be renamed to WhatsAppGatewayService in a follow-up.
    */
    /*
    |--------------------------------------------------------------------------
    | Lemon Squeezy (Billing / MoR)
    |--------------------------------------------------------------------------
    */
    'lemonsqueezy' => [
        'webhook_secret'     => env('LEMONSQUEEZY_WEBHOOK_SECRET', ''),
        'starter_checkout'   => env('LEMONSQUEEZY_STARTER_CHECKOUT', 'https://ot1pro.lemonsqueezy.com/checkout/buy/0b9fdfb5-56d1-4f07-b159-505b00dd3d42'),
        'pro_checkout'       => env('LEMONSQUEEZY_PRO_CHECKOUT', 'https://ot1pro.lemonsqueezy.com/checkout/buy/6646edff-0eea-414c-9e3f-0f9b37a3a0f1'),
    ],

    'wuzapi' => [
        'url'         => env('WUZAPI_URL', 'http://localhost:8082'),
        'admin_token' => env('WUZAPI_ADMIN_TOKEN', ''),
        'webhook_url' => env('WUZAPI_WEBHOOK_URL', ''),     // public URL Laravel receives webhooks on
        'webhook_host'=> env('WUZAPI_WEBHOOK_HOST', ''),    // optional Host header override (local dev)
        // Feature flag — controls whether the QR-Scan button is visible on the
        // Connections page. Set to false (default) until the gateway is proven
        // reliable enough for end users; flip to true to expose the button again.
        'qr_enabled'  => filter_var(env('WUZAPI_QR_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'indexnow' => [
        // Random 8-128 char hex string. Generate once with `php -r "echo bin2hex(random_bytes(16));"`
        // then set INDEXNOW_KEY in .env. The key is served at /{key}.txt to prove domain ownership.
        'key'  => env('INDEXNOW_KEY'),
        'host' => env('INDEXNOW_HOST', parse_url(env('APP_URL', ''), PHP_URL_HOST)),
    ],

    'google_indexing' => [
        // Path to Google Cloud service account JSON key file. The service account must be added
        // as an "Owner" in Search Console for the property.
        // Enable "Indexing API" in the Google Cloud project.
        'credentials' => env('GOOGLE_INDEXING_CREDENTIALS'),
    ],

    'google_ads' => [
        'conversion_id'   => env('GOOGLE_ADS_CONVERSION_ID'),   // e.g. AW-1234567890
        'signup_label'    => env('GOOGLE_ADS_SIGNUP_LABEL'),    // conversion label from Google Ads UI
        'whatsapp_label'  => env('GOOGLE_ADS_WHATSAPP_LABEL'),
        'form_label'      => env('GOOGLE_ADS_FORM_LABEL'),
    ],

    'media' => [
        'ingest_enabled'         => env('MEDIA_INGEST_ENABLED', true),
        'signed_url_ttl_days'    => 7,
        'max_upload_image_bytes' => 5 * 1024 * 1024,
        'max_upload_audio_bytes' => 16 * 1024 * 1024,
    ],

    'ai_media' => [
        'vision_enabled'        => env('VISION_ENABLED', true),
        'transcription_enabled' => env('TRANSCRIPTION_ENABLED', true),
    ],

    'groq' => [
        'enabled' => env('TRANSCRIPTION_GROQ_ENABLED', true),
        'api_key' => env('GROQ_API_KEY'),
        'model'   => env('GROQ_WHISPER_MODEL', 'whisper-large-v3'),
        'timeout' => (int) env('GROQ_TIMEOUT_SECONDS', 5),
    ],

    'whisper_cpp' => [
        'bin'     => env('WHISPER_CPP_BIN', '/usr/local/bin/whisper.cpp'),
        'model'   => env('WHISPER_CPP_MODEL', '/opt/whisper-models/ggml-medium.bin'),
        'threads' => (int) env('WHISPER_CPP_THREADS', 2),
    ],

];
