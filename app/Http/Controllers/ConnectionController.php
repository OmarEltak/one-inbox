<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnforcePlanLimits;
use App\Services\Platforms\FacebookPlatform;
use App\Services\Platforms\TelegramPlatform;
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
}
