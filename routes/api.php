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
});

// Stripe webhook (uses Cashier's signature verification middleware)
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])
    ->name('stripe.webhook');
