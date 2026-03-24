<?php

use Illuminate\Support\Facades\Route;

// Webhook routes (public, no auth - platforms POST here)
Route::prefix('webhooks')->middleware('throttle:webhooks')->group(function () {
    Route::match(['get', 'post'], '/meta', [\App\Http\Controllers\Webhooks\MetaWebhookController::class, 'handle'])
        ->name('webhooks.meta');

    Route::post('/telegram', [\App\Http\Controllers\Webhooks\TelegramWebhookController::class, 'handle'])
        ->name('webhooks.telegram');

    // Evolution API (WhatsApp QR gateway) — receives MESSAGES_UPSERT, CONNECTION_UPDATE, QRCODE_UPDATED
    // Set EVOLUTION_WEBHOOK_URL=https://yourdomain.com/api/webhooks/evolution in .env
    Route::post('/evolution', [\App\Http\Controllers\Webhooks\EvolutionWebhookController::class, 'handle'])
        ->name('webhooks.evolution');

    // TikTok Business Messaging — Set webhook in TikTok Developer Portal
    Route::match(['get', 'post'], '/tiktok', [\App\Http\Controllers\Webhooks\TikTokWebhookController::class, 'handle'])
        ->name('webhooks.tiktok');

    // Snapchat — Set webhook in Snap Kit Developer Portal
    Route::match(['get', 'post'], '/snapchat', [\App\Http\Controllers\Webhooks\SnapchatWebhookController::class, 'handle'])
        ->name('webhooks.snapchat');
});

// Stripe webhook (uses Cashier's signature verification middleware)
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])
    ->name('stripe.webhook');
