<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnforcePlanLimits;
use App\Jobs\FetchEmailsForPageJob;
use App\Services\Platforms\EmailPlatform;
use App\Services\Platforms\FacebookPlatform;
use App\Services\Platforms\TelegramPlatform;
use App\Services\Platforms\SnapchatPlatform;
use App\Services\Platforms\TikTokPlatform;
use App\Services\Platforms\WhatsAppPlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConnectionController extends Controller
{
    /**
     * Redirect to Facebook OAuth.
     */
    public function facebookRedirect(FacebookPlatform $facebook)
    {
        if (empty(config('services.meta.app_id')) || empty(config('services.meta.app_secret'))) {
            return redirect()->route('connections.index')
                ->with('error', 'Facebook is not configured yet. Set META_APP_ID and META_APP_SECRET in your .env file.');
        }

        $team = auth()->user()->currentTeam;
        if ($team && ! EnforcePlanLimits::canConnectPage($team)) {
            return redirect()->route('connections.index')
                ->with('error', 'You have reached your page limit. Please upgrade your plan to connect more pages.');
        }

        return redirect($facebook->getConnectUrl());
    }

    /**
     * Handle Facebook OAuth callback.
     */
    public function facebookCallback(Request $request, FacebookPlatform $facebook)
    {
        if ($request->has('error')) {
            Log::warning('Facebook OAuth error', [
                'error' => $request->input('error'),
                'reason' => $request->input('error_reason'),
            ]);

            return redirect()->route('connections.index')
                ->with('error', 'Facebook connection was cancelled or failed.');
        }

        try {
            $teamId = auth()->user()->current_team_id;
            $account = $facebook->handleCallback($request, $teamId);

            return redirect()->route('connections.index')
                ->with('success', "Connected {$account->name} with {$account->pages->count()} page(s).")
                ->with('syncing', true);
        } catch (\Throwable $e) {
            Log::error('Facebook OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('connections.index')
                ->with('error', 'Failed to connect Facebook: ' . $e->getMessage());
        }
    }

    /**
     * Redirect to Facebook OAuth to connect Instagram via Facebook Login.
     * Uses instagram_manage_messages — no app review required.
     */
    public function instagramViaFacebookRedirect(FacebookPlatform $facebook)
    {
        if (empty(config('services.meta.app_id')) || empty(config('services.meta.app_secret'))) {
            return redirect()->route('connections.index')
                ->with('error', 'Facebook is not configured yet. Set META_APP_ID and META_APP_SECRET in your .env file.');
        }

        $team = auth()->user()->currentTeam;
        if ($team && ! EnforcePlanLimits::canConnectPage($team)) {
            return redirect()->route('connections.index')
                ->with('error', 'You have reached your page limit. Please upgrade your plan to connect more pages.');
        }

        return redirect($facebook->getInstagramViaFacebookConnectUrl());
    }

    /**
     * Handle Instagram via Facebook OAuth callback.
     */
    public function instagramViaFacebookCallback(Request $request, FacebookPlatform $facebook)
    {
        if ($request->has('error') || ! $request->has('code')) {
            Log::warning('Instagram via Facebook OAuth error or cancel', [
                'error'  => $request->input('error'),
                'reason' => $request->input('error_reason'),
                'has_code' => $request->has('code'),
            ]);

            return redirect()->route('connections.index')
                ->with('error', 'Instagram connection was cancelled or failed.');
        }

        try {
            $teamId  = auth()->user()->current_team_id;
            $account = $facebook->handleInstagramViaFacebookCallback($request, $teamId);

            $igCount = $account->pages()->where('platform', 'instagram')->count();

            return redirect()->route('connections.index')
                ->with('success', "Connected {$account->name} — found {$igCount} Instagram account(s).")
                ->with('syncing', $igCount > 0);
        } catch (\Throwable $e) {
            Log::error('Instagram via Facebook OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('connections.index')
                ->with('error', 'Failed to connect Instagram: ' . $e->getMessage());
        }
    }

    /**
     * Redirect to Facebook OAuth for Instagram connection.
     */
    public function instagramRedirect(FacebookPlatform $facebook)
    {
        if (empty(config('services.meta.app_id')) || empty(config('services.meta.app_secret'))) {
            return redirect()->route('connections.index')
                ->with('error', 'Facebook is not configured yet. Set META_APP_ID and META_APP_SECRET in your .env file.');
        }

        return redirect($facebook->getInstagramConnectUrl());
    }

    /**
     * Handle Instagram OAuth callback.
     */
    public function instagramCallback(Request $request, FacebookPlatform $facebook)
    {
        if ($request->has('error')) {
            return redirect()->route('connections.index')
                ->with('error', 'Instagram connection was cancelled or failed.');
        }

        try {
            $teamId = auth()->user()->current_team_id;
            $account = $facebook->handleInstagramCallback($request, $teamId);

            $igCount = $account->pages()->where('platform', 'instagram')->count();

            return redirect()->route('connections.index')
                ->with('success', "Connected Instagram: found {$igCount} account(s).")
                ->with('syncing', $igCount > 0);
        } catch (\Throwable $e) {
            Log::error('Instagram OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('connections.index')
                ->with('error', 'Failed to connect Instagram: ' . $e->getMessage());
        }
    }

    /**
     * Handle WhatsApp Business connection via WABA ID + System User Token.
     */
    public function whatsappConnect(Request $request, WhatsAppPlatform $whatsapp)
    {
        $team = auth()->user()->currentTeam;
        if ($team && ! EnforcePlanLimits::canConnectPage($team)) {
            return redirect()->route('connections.index')
                ->with('error', 'You have reached your page limit. Please upgrade your plan to connect more pages.');
        }

        $request->validate([
            'waba_id' => 'required|string',
            'access_token' => 'required|string',
        ]);

        try {
            $teamId = auth()->user()->current_team_id;
            $account = $whatsapp->handleCallback($request, $teamId);

            $phoneCount = $account->pages()->where('platform', 'whatsapp')->count();

            return redirect()->route('connections.index')
                ->with('success', "Connected WhatsApp Business with {$phoneCount} phone number(s).");
        } catch (\Throwable $e) {
            Log::error('WhatsApp connection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('connections.index')
                ->with('error', 'Failed to connect WhatsApp: ' . $e->getMessage());
        }
    }

    /**
     * Handle Telegram Bot connection via Bot Token from BotFather.
     */
    public function telegramConnect(Request $request, TelegramPlatform $telegram)
    {
        $team = auth()->user()->currentTeam;
        if ($team && ! EnforcePlanLimits::canConnectPage($team)) {
            return redirect()->route('connections.index')
                ->with('error', 'You have reached your page limit. Please upgrade your plan to connect more pages.');
        }

        $request->validate([
            'bot_token' => 'required|string',
        ]);

        try {
            $teamId = auth()->user()->current_team_id;
            $account = $telegram->handleCallback($request, $teamId);

            $botName = $account->name;

            return redirect()->route('connections.index')
                ->with('success', "Connected Telegram bot: {$botName}");
        } catch (\Throwable $e) {
            Log::error('Telegram connection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('connections.index')
                ->with('error', 'Failed to connect Telegram: ' . $e->getMessage());
        }
    }

    /**
     * Redirect to TikTok OAuth.
     */
    public function tiktokRedirect(TikTokPlatform $tiktok)
    {
        if (empty(config('services.tiktok.client_key')) || empty(config('services.tiktok.client_secret'))) {
            return redirect()->route('connections.index')
                ->with('error', 'TikTok is not configured yet. Set TIKTOK_CLIENT_KEY and TIKTOK_CLIENT_SECRET in your .env file.');
        }

        $team = auth()->user()->currentTeam;
        if ($team && ! EnforcePlanLimits::canConnectPage($team)) {
            return redirect()->route('connections.index')
                ->with('error', 'You have reached your page limit. Please upgrade your plan to connect more pages.');
        }

        return redirect($tiktok->getConnectUrl());
    }

    /**
     * Handle TikTok OAuth callback.
     */
    public function tiktokCallback(Request $request, TikTokPlatform $tiktok)
    {
        if ($request->has('error')) {
            return redirect()->route('connections.index')
                ->with('error', 'TikTok connection was cancelled or failed.');
        }

        try {
            $teamId = auth()->user()->current_team_id;
            $account = $tiktok->handleCallback($request, $teamId);

            return redirect()->route('connections.index')
                ->with('success', "Connected TikTok account: {$account->name}");
        } catch (\Throwable $e) {
            Log::error('TikTok OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('connections.index')
                ->with('error', 'Failed to connect TikTok: ' . $e->getMessage());
        }
    }

    /**
     * Redirect to Snapchat OAuth.
     */
    public function snapchatRedirect(SnapchatPlatform $snapchat)
    {
        if (empty(config('services.snapchat.marketing_client_id')) || empty(config('services.snapchat.marketing_client_secret'))) {
            return redirect()->route('connections.index')
                ->with('error', 'Snapchat is not configured yet. Set SNAPCHAT_MARKETING_CLIENT_ID and SNAPCHAT_MARKETING_CLIENT_SECRET in your .env file.');
        }

        $team = auth()->user()->currentTeam;
        if ($team && ! EnforcePlanLimits::canConnectPage($team)) {
            return redirect()->route('connections.index')
                ->with('error', 'You have reached your page limit. Please upgrade your plan to connect more pages.');
        }

        return redirect($snapchat->getConnectUrl());
    }

    /**
     * Handle Email (IMAP/SMTP) connection via credential form.
     */
    public function emailConnect(Request $request, EmailPlatform $email)
    {
        $team = auth()->user()->currentTeam;
        if ($team && ! EnforcePlanLimits::canConnectPage($team)) {
            return redirect()->route('connections.index')
                ->with('error', 'You have reached your page limit. Please upgrade your plan to connect more pages.');
        }

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Auto-detect IMAP/SMTP settings from domain; allow manual override if provided
        $domain   = strtolower(substr(strrchr($request->input('email'), '@'), 1));
        $presets  = [
            'gmail.com'      => ['imap_host' => 'imap.gmail.com',           'imap_port' => 993, 'imap_encryption' => 'ssl', 'smtp_host' => 'smtp.gmail.com',           'smtp_port' => 587, 'smtp_encryption' => 'tls'],
            'googlemail.com' => ['imap_host' => 'imap.gmail.com',           'imap_port' => 993, 'imap_encryption' => 'ssl', 'smtp_host' => 'smtp.gmail.com',           'smtp_port' => 587, 'smtp_encryption' => 'tls'],
            'outlook.com'    => ['imap_host' => 'outlook.office365.com',    'imap_port' => 993, 'imap_encryption' => 'ssl', 'smtp_host' => 'smtp.office365.com',       'smtp_port' => 587, 'smtp_encryption' => 'tls'],
            'hotmail.com'    => ['imap_host' => 'outlook.office365.com',    'imap_port' => 993, 'imap_encryption' => 'ssl', 'smtp_host' => 'smtp.office365.com',       'smtp_port' => 587, 'smtp_encryption' => 'tls'],
            'live.com'       => ['imap_host' => 'outlook.office365.com',    'imap_port' => 993, 'imap_encryption' => 'ssl', 'smtp_host' => 'smtp.office365.com',       'smtp_port' => 587, 'smtp_encryption' => 'tls'],
            'yahoo.com'      => ['imap_host' => 'imap.mail.yahoo.com',      'imap_port' => 993, 'imap_encryption' => 'ssl', 'smtp_host' => 'smtp.mail.yahoo.com',      'smtp_port' => 465, 'smtp_encryption' => 'ssl'],
            'icloud.com'     => ['imap_host' => 'imap.mail.me.com',         'imap_port' => 993, 'imap_encryption' => 'ssl', 'smtp_host' => 'smtp.mail.me.com',         'smtp_port' => 587, 'smtp_encryption' => 'tls'],
            'me.com'         => ['imap_host' => 'imap.mail.me.com',         'imap_port' => 993, 'imap_encryption' => 'ssl', 'smtp_host' => 'smtp.mail.me.com',         'smtp_port' => 587, 'smtp_encryption' => 'tls'],
        ];

        $autoDetected = $presets[$domain] ?? ['imap_host' => "imap.{$domain}", 'imap_port' => 993, 'imap_encryption' => 'ssl', 'smtp_host' => "smtp.{$domain}", 'smtp_port' => 587, 'smtp_encryption' => 'tls'];

        // Only fill in values not already provided by the user (advanced fields)
        foreach ($autoDetected as $key => $value) {
            if (! $request->filled($key)) {
                $request->merge([$key => $value]);
            }
        }

        try {
            $teamId  = auth()->user()->current_team_id;
            $account = $email->handleCallback($request, $teamId);

            // Immediately fetch existing emails in the background
            $page = $account->pages()->where('platform', 'email')->first();
            if ($page) {
                FetchEmailsForPageJob::dispatch($page->id);
            }

            return redirect()->route('connections.index')
                ->with('success', "Connected email inbox: {$account->name}. Fetching your emails in the background…");
        } catch (\Throwable $e) {
            Log::error('Email connection failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('connections.index')
                ->with('error', 'Failed to connect email: ' . $e->getMessage());
        }
    }

    /**
     * Handle Snapchat OAuth callback.
     */
    public function snapchatCallback(Request $request, SnapchatPlatform $snapchat)
    {
        if ($request->has('error')) {
            return redirect()->route('connections.index')
                ->with('error', 'Snapchat connection was cancelled or failed.');
        }

        try {
            $teamId = auth()->user()->current_team_id;
            $account = $snapchat->handleCallback($request, $teamId);

            return redirect()->route('connections.index')
                ->with('success', "Connected Snapchat account: {$account->name}");
        } catch (\Throwable $e) {
            Log::error('Snapchat OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('connections.index')
                ->with('error', 'Failed to connect Snapchat: ' . $e->getMessage());
        }
    }
}
