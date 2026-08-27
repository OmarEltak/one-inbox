<?php

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;

// Google OAuth
Route::get('auth/google', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])->name('auth.google.callback');

// Homepage: renders the AI Campaign Manager landing (was welcome.blade.php until 2026-07-09).
// The old welcome view is preserved in resources/views/welcome.blade.php (content wrapped
// in @if(false) as a reference/rollback point — DO NOT delete or rename).
Route::view('/', 'pages.ai-campaign-manager')->name('home');

// Marketing pages
Route::view('about', 'pages.about')->name('about');
Route::view('contact', 'pages.contact')->name('contact');
Route::view('privacy', 'pages.privacy')->name('privacy');
Route::view('terms', 'pages.terms')->name('terms');
Route::view('refund', 'pages.refund')->name('refund');

// Wire transfer payment page (public)
Route::get('/pay-wire', \App\Livewire\PayWire::class)->name('pay-wire');

// Lemon Squeezy checkout — commented out; replaced by manual wire transfer flow
// Route::middleware('auth')->get('/billing/checkout/{plan}', [\App\Http\Controllers\Billing\CheckoutController::class, 'redirect'])
//     ->name('billing.checkout')
//     ->where('plan', 'starter|pro');
Route::view('pricing', 'pages.pricing')->name('pricing');
Route::view('features', 'pages.features')->name('features');
// Find-Your-Fit interactive quiz. `noindex` — this page is a conversion
// destination for traffic that already arrived (from blogs, ads, direct).
// It is NOT an SEO landing page. CTAs deliberately unwired until Meta App
// Review completes; see CLAUDE.md pin #1.
Route::view('find-your-fit', 'pages.find-your-fit')->name('find-your-fit');
// /ai-campaign-manager kept as a 301 to the canonical homepage `/` so any inbound
// links (backlinks, blog references, sitemap history) don't 404. Named route preserved
// so existing `route('ai-campaign-manager')` calls in views resolve to the same URL.
Route::redirect('ai-campaign-manager', '/', 301)->name('ai-campaign-manager');

// Platform landing pages
Route::view('whatsapp-inbox', 'pages.whatsapp-inbox')->name('whatsapp-inbox');
Route::view('instagram-dm', 'pages.instagram-dm')->name('instagram-dm');
Route::view('facebook-messenger', 'pages.facebook-messenger')->name('facebook-messenger');
Route::view('telegram-inbox', 'pages.telegram-inbox')->name('telegram-inbox');

// Blog
Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Comparison pages
Route::view('vs/trengo', 'pages.vs.trengo')->name('vs.trengo');
Route::view('vs/manychat', 'pages.vs.manychat')->name('vs.manychat');
Route::view('vs/tidio', 'pages.vs.tidio')->name('vs.tidio');
Route::view('vs/wati', 'pages.vs.wati')->name('vs.wati');
Route::view('vs/aisensy', 'pages.vs.aisensy')->name('vs.aisensy');

// SEO consolidation redirects (per audit 2026-07-25):
//   - /vs/respond-io was cannibalising the pos-1 blog post; consolidate signal there.
//   - /vs/freshchat had 0 impressions and Freshchat is being deprioritised by Freshworks.
//   - Named routes preserved so existing route('vs.freshchat') / route('vs.respond-io')
//     calls in views resolve to the redirect target.
Route::redirect('vs/respond-io', '/blog/ot1pro-vs-respond-io-unified-inbox-comparison', 301)->name('vs.respond-io');
Route::redirect('vs/freshchat', '/vs/manychat', 301)->name('vs.freshchat');

// Industry landing pages
Route::view('industries/real-estate', 'pages.industries.real-estate')->name('industry.real-estate');
Route::view('industries/ecommerce', 'pages.industries.ecommerce')->name('industry.ecommerce');
Route::view('industries/agencies', 'pages.industries.agencies')->name('industry.agencies');
Route::view('industries/restaurants', 'pages.industries.restaurants')->name('industry.restaurants');
Route::view('industries/dropshipping', 'pages.industries.dropshipping')->name('industry.dropshipping');

// Industry consolidation: education had 0 impressions + long procurement cycles.
// Redirect to ecommerce (the ICP page per our Meta ads plan).
Route::redirect('industries/education', '/industries/ecommerce', 301)->name('industry.education');

// Programmatic vertical landing pages targeting "unified inbox for [role]"
// long-tail keywords. Route constraint list must match VerticalLandingController::ROLES keys.
Route::get('unified-inbox-for-{role}', [\App\Http\Controllers\VerticalLandingController::class, 'show'])
    ->where('role', 'engineering-managers|sales-teams|support-teams|agencies|customer-success-teams|devops-teams|hr-teams|marketing-teams')
    ->name('vertical-landing');

// Public status page for Meta data deletion requests (referenced from the
// callback's JSON response so end-users can check status).
Route::get('data-deletion/status/{code}', [\App\Http\Controllers\Webhooks\MetaDataDeletionController::class, 'status'])
    ->name('data-deletion.status')
    ->where('code', '[A-Za-z0-9]{40}');

// Public email tracking + unsubscribe (signed URLs).
Route::middleware('signed')->group(function () {
    Route::get('e/o/{recipient}', [\App\Http\Controllers\EmailTrackingController::class, 'open'])
        ->name('email.track.open');
    Route::get('e/u/{recipient}', [\App\Http\Controllers\EmailTrackingController::class, 'unsubscribeShow'])
        ->name('email.unsubscribe.show');
    Route::post('e/u/{recipient}', [\App\Http\Controllers\EmailTrackingController::class, 'unsubscribeConfirm'])
        ->name('email.unsubscribe.confirm');
});

// IndexNow ownership verification — Bing/Yandex fetch /{key}.txt to prove control of the domain.
Route::get('{key}.txt', function (string $key) {
    $configured = (string) config('services.indexnow.key', '');
    abort_if($configured === '' || ! hash_equals($configured, $key), 404);
    return response($configured, 200, ['Content-Type' => 'text/plain']);
})->where('key', '[a-f0-9]{8,128}');

// Sitemap
Route::get('sitemap.xml', function () {
    $today = now()->toDateString();

    $pages = [
        ['loc' => url('/'),                          'priority' => '1.0', 'changefreq' => 'weekly',  'lastmod' => $today],
        ['loc' => url('/features'),                  'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/pricing'),                   'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/blog'),                      'priority' => '0.9', 'changefreq' => 'weekly',  'lastmod' => $today],
        ['loc' => url('/whatsapp-inbox'),            'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/instagram-dm'),              'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/facebook-messenger'),        'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/telegram-inbox'),            'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/vs/trengo'),                 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/vs/manychat'),               'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/vs/tidio'),                  'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/vs/wati'),                   'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/vs/aisensy'),                'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/industries/real-estate'),    'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/industries/ecommerce'),      'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/industries/agencies'),       'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/industries/restaurants'),    'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/industries/dropshipping'),   'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today],
    ];

    // Programmatic vertical landing pages — one per supported role
    foreach (\App\Http\Controllers\VerticalLandingController::roleSlugs() as $roleSlug) {
        $pages[] = [
            'loc'        => url('/unified-inbox-for-' . $roleSlug),
            'priority'   => '0.8',
            'changefreq' => 'monthly',
            'lastmod'    => $today,
        ];
    }

    $pages = array_merge($pages, [
        ['loc' => url('/about'),                     'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/contact'),                   'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $today],
        ['loc' => url('/privacy'),                   'priority' => '0.3', 'changefreq' => 'yearly',  'lastmod' => '2025-01-01'],
        ['loc' => url('/terms'),                     'priority' => '0.3', 'changefreq' => 'yearly',  'lastmod' => '2025-01-01'],
        ['loc' => url('/refund'),                    'priority' => '0.3', 'changefreq' => 'yearly',  'lastmod' => '2026-07-04'],
    ]);

    // Add published blog posts
    $posts       = \App\Models\Post::published()->orderByDesc('published_at')->get();
    $latestPost  = $posts->max('updated_at');
    $lastModified = $latestPost?->toRfc7231String() ?? now()->toRfc7231String();
    foreach ($posts as $post) {
        $pages[] = [
            'loc'        => route('blog.show', $post->slug),
            'priority'   => '0.7',
            'changefreq' => 'monthly',
            'lastmod'    => $post->updated_at?->toDateString() ?? $today,
        ];
    }

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    foreach ($pages as $url) {
        $loc  = htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $arLoc = htmlspecialchars($url['loc'] . '?lang=ar', ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$loc}</loc>\n";
        $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
        $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
        $xml .= "    <priority>{$url['priority']}</priority>\n";
        $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"en\" href=\"{$loc}\"/>\n";
        $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"ar\" href=\"{$arLoc}\"/>\n";
        $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$loc}\"/>\n";
        $xml .= "  </url>\n";
    }

    $xml .= '</urlset>';

    return response($xml, 200, [
        'Content-Type'  => 'application/xml; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600, s-maxage=21600',
        'Last-Modified' => $lastModified,
    ]);
})->name('sitemap');

// llms.txt — canonical site summary for AI crawlers (ChatGPT, Perplexity, Claude, Cursor, etc.)
// Spec: https://llmstxt.org
// This is what AI search engines will cite when asked "what is OT1-Pro?".
Route::get('llms.txt', function () {
    $body = <<<'TXT'
# OT1-Pro

> OT1-Pro is a unified social inbox and AI sales responder for small businesses. It brings WhatsApp Business, Instagram Direct, Facebook Messenger, Telegram, and email conversations into a single shared team inbox, and can auto-reply with an AI sales agent that qualifies leads and closes deals 24/7.

Built for Arabic and English-speaking small-to-mid ecommerce brands, agencies, real estate teams, clinics, and D2C stores that receive 50-1000+ customer messages per day across social platforms and cannot afford to miss sales at night.

## What OT1-Pro does

- Unified inbox: Reply to Facebook Messenger, Instagram DMs, WhatsApp Business, Telegram, and email from one screen.
- AI sales responder: Auto-replies to customer messages in your brand voice, qualifies leads, books appointments, answers FAQs, and hands over to humans on high-intent conversations.
- Shared team inbox: Assign conversations to teammates, add internal notes, track response times, and see who is handling what in real time.
- Multi-tenant SaaS: Each team gets isolated pages, contacts, conversations, and AI training data.
- Language: Full Arabic and English UI. AI replies in the language the customer wrote in.
- Region: Optimised for Egypt, Saudi Arabia, UAE, and MENA, but works globally.

## Pricing (USD, monthly)

- Free: 1 connected page, 20 AI responses/month, 1 user. No credit card.
- Basic $8: 1 page, 100 AI responses/month, 1 user.
- Starter $29: 3 pages, 500 AI responses/month, all 4 platforms, lead scoring, 3 users.
- Pro $79: 5 pages, 2,000 AI responses/month, all platforms, advanced analytics, AI bulk campaigns, 10 users, priority support.
- Enterprise: Custom pricing for agencies and large teams. Contact via WhatsApp.

## Key differentiators vs alternatives

- Arabic-first product (vs Respond.io, ManyChat, WATI, AiSensy which are English-only or English-primary).
- All 4 social channels plus email in one inbox at $29 (vs Respond.io starting at $79 without AI).
- AI reply generation included at the $8 tier (competitors charge $50-200 more for AI features).
- Founder-accessible support via WhatsApp +201026361218.

## Alternatives comparison pages

- vs Respond.io: https://ot1-pro.com/vs/respond-io
- vs ManyChat: https://ot1-pro.com/vs/manychat
- vs Trengo: https://ot1-pro.com/vs/trengo
- vs Tidio: https://ot1-pro.com/vs/tidio
- vs Freshchat: https://ot1-pro.com/vs/freshchat

## Key pages

- Homepage: https://ot1-pro.com/
- Features: https://ot1-pro.com/features
- Pricing: https://ot1-pro.com/pricing
- WhatsApp Inbox: https://ot1-pro.com/whatsapp-inbox
- Instagram DM: https://ot1-pro.com/instagram-dm
- Facebook Messenger: https://ot1-pro.com/facebook-messenger
- Telegram Inbox: https://ot1-pro.com/telegram-inbox
- Blog: https://ot1-pro.com/blog
- Contact: https://ot1-pro.com/contact
- Privacy Policy: https://ot1-pro.com/privacy
- Terms of Service: https://ot1-pro.com/terms

## Contact

- Website: https://ot1-pro.com
- WhatsApp sales: +20 102 636 1218
- Email: support@ot1-pro.com
TXT;

    return response($body, 200, [
        'Content-Type'  => 'text/plain; charset=UTF-8',
        'Cache-Control' => 'public, max-age=86400, s-maxage=86400',
    ]);
})->name('llms');

Route::middleware(['auth', 'verified', 'team', 'throttle:60,1'])->group(function () {
    Route::get('dashboard', \App\Livewire\Dashboard::class)->middleware('permission:dashboard')->name('dashboard');

    // Inbox
    Route::get('inbox', \App\Livewire\Inbox\Index::class)->middleware(['permission:inbox', 'require.connection'])->name('inbox');

    // Contacts
    Route::get('contacts', \App\Livewire\Contacts\Index::class)->middleware(['permission:contacts', 'require.connection'])->name('contacts.index');

    // Connections (connected accounts/pages)
    Route::middleware('permission:connections')->group(function () {
        Route::get('connections', \App\Livewire\Connections\Index::class)->name('connections.index');
        Route::get('connections/facebook/redirect', [\App\Http\Controllers\ConnectionController::class, 'facebookRedirect'])->name('connections.facebook.redirect');
        Route::get('connections/facebook/callback', [\App\Http\Controllers\ConnectionController::class, 'facebookCallback'])->name('connections.facebook.callback');
        Route::get('connections/instagram/redirect', [\App\Http\Controllers\ConnectionController::class, 'instagramRedirect'])->name('connections.instagram.redirect');
        Route::get('connections/instagram/callback', [\App\Http\Controllers\ConnectionController::class, 'instagramCallback'])->name('connections.instagram.callback');
        Route::get('connections/instagram-via-facebook/redirect', [\App\Http\Controllers\ConnectionController::class, 'instagramViaFacebookRedirect'])->name('connections.instagram-via-facebook.redirect');
        Route::get('connections/instagram-via-facebook/callback', [\App\Http\Controllers\ConnectionController::class, 'instagramViaFacebookCallback'])->name('connections.instagram-via-facebook.callback');
        Route::post('connections/whatsapp/connect', [\App\Http\Controllers\ConnectionController::class, 'whatsappConnect'])->name('connections.whatsapp.connect');
        Route::post('connections/telegram/connect', [\App\Http\Controllers\ConnectionController::class, 'telegramConnect'])->name('connections.telegram.connect');
        Route::get('connections/tiktok/redirect', [\App\Http\Controllers\ConnectionController::class, 'tiktokRedirect'])->name('connections.tiktok.redirect');
        Route::get('connections/tiktok/callback', [\App\Http\Controllers\ConnectionController::class, 'tiktokCallback'])->name('connections.tiktok.callback');
        Route::get('connections/snapchat/redirect', [\App\Http\Controllers\ConnectionController::class, 'snapchatRedirect'])->name('connections.snapchat.redirect');
        Route::get('connections/snapchat/callback', [\App\Http\Controllers\ConnectionController::class, 'snapchatCallback'])->name('connections.snapchat.callback');
        Route::post('connections/email/connect', [\App\Http\Controllers\ConnectionController::class, 'emailConnect'])->name('connections.email.connect');
        Route::post('connections/slack/connect', [\App\Http\Controllers\ConnectionController::class, 'slackConnect'])->name('connections.slack.connect');
        Route::post('connections/discord/connect', [\App\Http\Controllers\ConnectionController::class, 'discordConnect'])->name('connections.discord.connect');
    });

    // Campaigns
    Route::get('campaigns', \App\Livewire\Campaigns\Index::class)->middleware(['permission:connections', 'require.connection'])->name('campaigns.index');
    Route::get('campaigns/email/new', \App\Livewire\Campaigns\EmailWizard::class)->middleware(['permission:connections', 'require.connection'])->name('campaigns.email.new');
    Route::get('campaigns/whatsapp/new', \App\Livewire\Campaigns\WhatsAppWizard::class)->middleware(['permission:connections', 'require.connection'])->name('campaigns.whatsapp.new');
    Route::get('campaigns/{campaign}', \App\Livewire\Campaigns\Show::class)->middleware(['permission:connections', 'require.connection'])->name('campaigns.show');

    // Content
    Route::get('content', \App\Livewire\Content\Index::class)->middleware(['permission:connections', 'require.connection'])->name('content.index');

    // AI Chat
    Route::get('ai-chat', \App\Livewire\AiChat::class)->middleware(['permission:ai-chat', 'require.connection'])->name('ai-chat');

    // Analytics
    Route::get('analytics', \App\Livewire\Analytics::class)->middleware(['permission:analytics', 'require.connection'])->name('analytics');

    // AI Settings
    Route::middleware(['permission:ai-settings', 'require.connection'])->group(function () {
        Route::get('settings/ai', \App\Livewire\Settings\AiSettings::class)->name('settings.ai');
        Route::get('settings/ai/config', \App\Livewire\Settings\AiConfig::class)->name('settings.ai.config');
    });

    // Admin Management
    Route::get('settings/admins', \App\Livewire\Settings\AdminManagement::class)->middleware('permission:manage-admins')->name('settings.admins');

    // Quick Replies
    Route::get('settings/quick-replies', \App\Livewire\Settings\QuickReplies::class)->middleware('permission:ai-settings')->name('settings.quick-replies');

    // Webhook Logs (head admin only via manage-admins permission)
    Route::get('settings/webhook-logs', \App\Livewire\Settings\WebhookLogs::class)->middleware('permission:manage-admins')->name('settings.webhook-logs');

    // Super-admin (OT AI staff) only — manage customer workspaces and page assignments.
    Route::middleware('super-admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('customers', \App\Livewire\SuperAdmin\Customers::class)->name('customers');
        Route::get('subscriptions', \App\Livewire\SuperAdmin\Subscriptions::class)->name('subscriptions');
        Route::get('page-assignments', \App\Livewire\SuperAdmin\PageAssignments::class)->name('page-assignments');
        Route::get('onboarding-requests', \App\Livewire\SuperAdmin\OnboardingRequests::class)->name('onboarding-requests');

        // Blog admin
        Route::get('blog', \App\Livewire\SuperAdmin\Blog\Index::class)->name('blog.index');
        Route::get('blog/create', \App\Livewire\SuperAdmin\Blog\Editor::class)->name('blog.create');
        Route::get('blog/{post}/edit', \App\Livewire\SuperAdmin\Blog\Editor::class)->name('blog.edit');
    });
});

// Team creation (for users without a team)
Route::middleware(['auth'])->group(function () {
    Route::get('teams/create', \App\Livewire\Teams\Create::class)->name('teams.create');

    Route::post('teams/{team}/switch', function (\App\Models\Team $team, \Illuminate\Http\Request $request) {
        $user = $request->user();
        $isMember = $user->teams()->whereKey($team->id)->exists();
        abort_unless($isMember || $user->isSuperAdmin(), 403);
        $user->switchTeam($team);
        return redirect()->back();
    })->name('teams.switch');
});

Route::get('/media/{ulid}', [\App\Http\Controllers\MediaController::class, 'stream'])
    ->name('media.stream')
    ->middleware('signed');

require __DIR__.'/settings.php';
