<?php

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Marketing pages
Route::view('about', 'pages.about')->name('about');
Route::view('contact', 'pages.contact')->name('contact');
Route::view('privacy', 'pages.privacy')->name('privacy');
Route::view('terms', 'pages.terms')->name('terms');
Route::view('pricing', 'pages.pricing')->name('pricing');
Route::view('features', 'pages.features')->name('features');

// Sitemap
Route::get('sitemap.xml', function () {
    $urls = [
        ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'weekly'],
        ['loc' => url('/features'), 'priority' => '0.9', 'changefreq' => 'monthly'],
        ['loc' => url('/pricing'), 'priority' => '0.9', 'changefreq' => 'monthly'],
        ['loc' => url('/about'), 'priority' => '0.7', 'changefreq' => 'monthly'],
        ['loc' => url('/contact'), 'priority' => '0.7', 'changefreq' => 'monthly'],
        ['loc' => url('/privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
        ['loc' => url('/terms'), 'priority' => '0.3', 'changefreq' => 'yearly'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $url) {
        $xml .= '<url>';
        $xml .= '<loc>' . $url['loc'] . '</loc>';
        $xml .= '<changefreq>' . $url['changefreq'] . '</changefreq>';
        $xml .= '<priority>' . $url['priority'] . '</priority>';
        $xml .= '</url>';
    }
    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::middleware(['auth', 'verified', 'team', 'throttle:60,1'])->group(function () {
    Route::get('dashboard', \App\Livewire\Dashboard::class)->middleware('permission:dashboard')->name('dashboard');

    // Inbox
    Route::get('inbox', \App\Livewire\Inbox\Index::class)->middleware('permission:inbox')->name('inbox');

    // Contacts
    Route::get('contacts', \App\Livewire\Contacts\Index::class)->middleware('permission:contacts')->name('contacts.index');

    // Connections (connected accounts/pages)
    Route::middleware('permission:connections')->group(function () {
        Route::get('connections', \App\Livewire\Connections\Index::class)->name('connections.index');
        Route::get('connections/facebook/redirect', [\App\Http\Controllers\ConnectionController::class, 'facebookRedirect'])->name('connections.facebook.redirect');
        Route::get('connections/facebook/callback', [\App\Http\Controllers\ConnectionController::class, 'facebookCallback'])->name('connections.facebook.callback');
        Route::get('connections/instagram/redirect', [\App\Http\Controllers\ConnectionController::class, 'instagramRedirect'])->name('connections.instagram.redirect');
        Route::get('connections/instagram/callback', [\App\Http\Controllers\ConnectionController::class, 'instagramCallback'])->name('connections.instagram.callback');
        Route::post('connections/whatsapp/connect', [\App\Http\Controllers\ConnectionController::class, 'whatsappConnect'])->name('connections.whatsapp.connect');
        Route::post('connections/telegram/connect', [\App\Http\Controllers\ConnectionController::class, 'telegramConnect'])->name('connections.telegram.connect');
    });

    // Campaigns
    Route::get('campaigns', \App\Livewire\Campaigns\Index::class)->middleware('permission:connections')->name('campaigns.index');

    // AI Chat
    Route::get('ai-chat', \App\Livewire\AiChat::class)->middleware('permission:ai-chat')->name('ai-chat');

    // Analytics
    Route::get('analytics', \App\Livewire\Analytics::class)->middleware('permission:analytics')->name('analytics');

    // AI Settings
    Route::middleware('permission:ai-settings')->group(function () {
        Route::get('settings/ai', \App\Livewire\Settings\AiSettings::class)->name('settings.ai');
        Route::get('settings/ai/config', \App\Livewire\Settings\AiConfig::class)->name('settings.ai.config');
    });

    // Admin Management
    Route::get('settings/admins', \App\Livewire\Settings\AdminManagement::class)->middleware('permission:manage-admins')->name('settings.admins');

    // Quick Replies
    Route::get('settings/quick-replies', \App\Livewire\Settings\QuickReplies::class)->middleware('permission:ai-settings')->name('settings.quick-replies');

    // Webhook Logs (head admin only via manage-admins permission)
    Route::get('settings/webhook-logs', \App\Livewire\Settings\WebhookLogs::class)->middleware('permission:manage-admins')->name('settings.webhook-logs');
});

// Team creation (for users without a team)
Route::middleware(['auth'])->group(function () {
    Route::get('teams/create', \App\Livewire\Teams\Create::class)->name('teams.create');
});

require __DIR__.'/settings.php';
