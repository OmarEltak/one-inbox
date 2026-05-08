<?php

use Illuminate\Support\Facades\Route;

// Webhook routes (public, no auth - platforms POST here)
Route::prefix('webhooks')->middleware('throttle:webhooks')->group(function () {
    Route::match(['get', 'post'], '/meta', [\App\Http\Controllers\Webhooks\MetaWebhookController::class, 'handle'])
        ->name('webhooks.meta');

    Route::match(['get', 'post'], '/meta-ig', [\App\Http\Controllers\Webhooks\MetaWebhookController::class, 'handle'])
        ->name('webhooks.meta-ig');

    Route::post('/telegram', [\App\Http\Controllers\Webhooks\TelegramWebhookController::class, 'handle'])
        ->name('webhooks.telegram');

    // Evolution API (WhatsApp QR gateway) — receives MESSAGES_UPSERT, CONNECTION_UPDATE, QRCODE_UPDATED
    // Set EVOLUTION_WEBHOOK_URL=https://yourdomain.com/api/webhooks/evolution in .env
    // DEPRECATED: kept so any in-flight Evolution events still process; new connections use Wuzapi.
    Route::post('/evolution', [\App\Http\Controllers\Webhooks\EvolutionWebhookController::class, 'handle'])
        ->name('webhooks.evolution');

    // Wuzapi (whatsmeow-based WhatsApp QR gateway) — current primary path.
    // Set WUZAPI_WEBHOOK_URL=https://yourdomain.com/api/webhooks/wuzapi in .env
    Route::post('/wuzapi', [\App\Http\Controllers\Webhooks\WuzapiWebhookController::class, 'handle'])
        ->name('webhooks.wuzapi');

    // TikTok Business Messaging — Set webhook in TikTok Developer Portal
    Route::match(['get', 'post'], '/tiktok', [\App\Http\Controllers\Webhooks\TikTokWebhookController::class, 'handle'])
        ->name('webhooks.tiktok');

    // Snapchat — Set webhook in Snap Kit Developer Portal
    Route::match(['get', 'post'], '/snapchat', [\App\Http\Controllers\Webhooks\SnapchatWebhookController::class, 'handle'])
        ->name('webhooks.snapchat');

    // Slack Events API — set as Event Subscriptions Request URL in the Slack App config.
    Route::post('/slack', [\App\Http\Controllers\Webhooks\SlackWebhookController::class, 'handle'])
        ->name('webhooks.slack');

    // Discord Interactions — set as Interactions Endpoint URL in the Discord Application config.
    Route::post('/discord', [\App\Http\Controllers\Webhooks\DiscordInteractionController::class, 'handle'])
        ->name('webhooks.discord');
});

// WebChat widget — public endpoints called by widget.js on customers' sites.
// No auth: throttled by widget_id; visitor identity is an opaque cookie/localStorage value.
Route::prefix('webchat/{widget}')->middleware('throttle:60,1')->group(function () {
    Route::post('/visitor', [\App\Http\Controllers\WebChatController::class, 'visitor'])
        ->name('webchat.visitor');
    Route::post('/messages', [\App\Http\Controllers\WebChatController::class, 'send'])
        ->name('webchat.send');
    Route::get('/messages', [\App\Http\Controllers\WebChatController::class, 'poll'])
        ->name('webchat.poll');
    Route::get('/config', [\App\Http\Controllers\WebChatController::class, 'config'])
        ->name('webchat.config');
});

// Stripe webhook (uses Cashier's signature verification middleware)
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])
    ->name('stripe.webhook');

// Authenticated campaign endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/campaigns/preview', \App\Http\Controllers\Api\CampaignPreviewController::class)
        ->name('campaigns.preview');
});
