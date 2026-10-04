<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnforcePlanLimits;
use App\Jobs\FetchEmailsForPageJob;
use App\Models\Team;
use App\Services\Platforms\DiscordPlatform;
use App\Services\Platforms\EmailPlatform;
use App\Services\Platforms\FacebookPlatform;
use App\Services\Platforms\SlackPlatform;
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
        // Meta uses TWO different param name pairs depending on failure type:
        //   - User cancel / permission decline: ?error=access_denied&error_reason=...
        //   - Scope validation failure:         ?error_code=100&error_message=...
        // Check both so we never fall through to the OAuth exchange with a null code.
        if ($request->has('error') || $request->has('error_code')) {
            $metaMessage = $request->input('error_message') ?? $request->input('error_description');
            Log::warning('Facebook OAuth error', [
                'error'         => $request->input('error'),
                'error_code'    => $request->input('error_code'),
                'error_reason'  => $request->input('error_reason'),
                'error_message' => $metaMessage,
                'query'         => $request->query(),
            ]);

            return redirect()->route('connections.index')
                ->with('error', 'Facebook connection failed: ' . ($metaMessage ?: 'user cancelled or permissions denied'));
        }

        if (! $request->filled('code')) {
            Log::warning('Facebook OAuth callback missing code (no error param either)', [
                'query' => $request->query(),
            ]);
            return redirect()->route('connections.index')
                ->with('error', 'Facebook connection failed: Meta returned an empty response. Please try again.');
        }

        try {
            $teamId = auth()->user()->current_team_id;
            $account = $facebook->handleCallback($request, $teamId);

            $team = auth()->user()->currentTeam;
            $team?->clearActivePagesCache();
            if ($team) $this->maybePromptAiSetup($team);

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

            $team = auth()->user()->currentTeam;
            $team?->clearActivePagesCache();
            if ($team) $this->maybePromptAiSetup($team);

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
        // Same two failure shapes as facebookCallback(): ?error=… (cancel) and
        // ?error_code=…&error_message=… (app/role/scope rejection). Without the
        // second check a rejected login fell through to exchangeInstagramCode(null).
        if ($request->has('error') || $request->has('error_code') || ! $request->filled('code')) {
            $metaMessage = $request->input('error_message')
                ?? $request->input('error_description')
                ?? $request->input('error_reason');
            Log::warning('Instagram OAuth error or missing code', [
                'query' => $request->except('code'),
            ]);

            // Show what Instagram actually sent back so the cause is visible without
            // server-log access (values are escaped by Blade when rendered).
            $received = collect($request->except('code'))
                ->map(fn ($v, $k) => $k . '=' . (is_scalar($v) ? $v : json_encode($v)))
                ->implode(', ');

            return redirect()->route('connections.index')
                ->with('error', 'Instagram connection failed: ' . ($metaMessage ?: \Illuminate\Support\Str::limit(
                    'Instagram returned no authorization code (received: ' . ($received ?: 'nothing') . '). '
                    . 'Make sure the account is a Business/Creator account with an accepted Instagram Tester invite, then try again.',
                    500
                )));
        }

        try {
            $teamId = auth()->user()->current_team_id;
            $account = $facebook->handleInstagramCallback($request, $teamId);

            $team = auth()->user()->currentTeam;
            $team?->clearActivePagesCache();
            if ($team) $this->maybePromptAiSetup($team);

            $igPages = $account->pages()->where('platform', 'instagram')->get();
            $igCount = $igPages->count();

            if ($igPages->contains(fn ($p) => isset($p->metadata['subscription_error']))) {
                return redirect()->route('connections.index')
                    ->with('error', 'Instagram connected, but Meta refused the message subscription — DMs will not arrive. Please try connecting again.');
            }

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
     * Handle WhatsApp Business connection via Meta Embedded Signup.
     *
     * Called by resources/views/livewire/connections/index.blade.php after
     * FB.login({config_id, response_type: 'code'}) resolves. The browser
     * also listens for the 'WA_EMBEDDED_SIGNUP' postMessage event to pick
     * up waba_id + phone_number_id, which we receive here alongside the
     * short-lived auth code.
     *
     * We exchange the code for a user access token (Meta recommends the
     * client-side-exchange-free code flow precisely for Embedded Signup),
     * then reuse WhatsAppPlatform::handleCallback to:
     *   - fetch WABA details,
     *   - persist ConnectedAccount + Page rows for each phone number,
     *   - register phone numbers for Cloud API messaging,
     *   - subscribe the WABA to our webhook.
     *
     * The response is JSON because the caller is a fetch() from the
     * Embedded Signup popup resolution, not a form submit.
     */
    public function whatsappEmbeddedSignupCallback(Request $request, WhatsAppPlatform $whatsapp)
    {
        $team = auth()->user()->currentTeam;
        if ($team && ! EnforcePlanLimits::canConnectPage($team)) {
            return response()->json([
                'ok' => false,
                'message' => 'You have reached your page limit. Please upgrade your plan to connect more pages.',
            ], 403);
        }

        $data = $request->validate([
            'code'            => 'required|string',
            'waba_id'         => 'required|string',
            'phone_number_id' => 'nullable|string',
        ]);

        try {
            $appId = (string) config('services.meta.app_id');
            $appSecret = (string) config('services.meta.app_secret');
            $version = config('services.meta.graph_api_version', 'v21.0');

            if (empty($appId) || empty($appSecret)) {
                throw new \RuntimeException('Meta app not configured (META_APP_ID / META_APP_SECRET).');
            }

            // Embedded Signup uses the "code" flow — exchange with no redirect_uri.
            $tokenResp = \Illuminate\Support\Facades\Http::get(
                "https://graph.facebook.com/{$version}/oauth/access_token",
                [
                    'client_id'     => $appId,
                    'client_secret' => $appSecret,
                    'code'          => $data['code'],
                ]
            )->throw()->json();

            $accessToken = $tokenResp['access_token'] ?? null;
            if (empty($accessToken)) {
                throw new \RuntimeException('Meta did not return an access token for the Embedded Signup code.');
            }

            // Reuse the existing WhatsApp onboarding path.
            $teamId = auth()->user()->current_team_id;
            $callbackRequest = new Request([
                'waba_id'      => $data['waba_id'],
                'access_token' => $accessToken,
            ]);
            $account = $whatsapp->handleCallback($callbackRequest, $teamId);

            $team = auth()->user()->currentTeam;
            $team?->clearActivePagesCache();
            if ($team) $this->maybePromptAiSetup($team);

            $phoneCount = $account->pages()->where('platform', 'whatsapp')->count();

            return response()->json([
                'ok' => true,
                'message' => "Connected WhatsApp Business with {$phoneCount} phone number(s).",
                'phone_count' => $phoneCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp Embedded Signup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Failed to connect WhatsApp: ' . $e->getMessage(),
            ], 500);
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

            $team = auth()->user()->currentTeam;
            $team?->clearActivePagesCache();
            if ($team) $this->maybePromptAiSetup($team);

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

            $team = auth()->user()->currentTeam;
            $team?->clearActivePagesCache();
            if ($team) $this->maybePromptAiSetup($team);

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
     * Connect a Slack workspace via Bot User OAuth Token + Signing Secret.
     */
    public function slackConnect(Request $request, SlackPlatform $slack)
    {
        $team = auth()->user()->currentTeam;
        if ($team && ! EnforcePlanLimits::canConnectPage($team)) {
            return redirect()->route('connections.index')
                ->with('error', 'You have reached your page limit. Please upgrade your plan to connect more pages.');
        }

        $request->validate([
            'bot_token'      => 'required|string|max:255',
            'signing_secret' => 'required|string|max:255',
        ]);

        try {
            $teamId = auth()->user()->current_team_id;
            $account = $slack->handleCallback($request, $teamId);
            $team = auth()->user()->currentTeam;
            $team?->clearActivePagesCache();
            if ($team) $this->maybePromptAiSetup($team);
            return redirect()->route('connections.index')
                ->with('success', "Connected Slack workspace: {$account->name}");
        } catch (\Throwable $e) {
            Log::error('Slack connection failed', ['error' => $e->getMessage()]);
            return redirect()->route('connections.index')
                ->with('error', 'Failed to connect Slack: ' . $e->getMessage());
        }
    }

    /**
     * Connect a Discord application via Bot Token + Application ID + Public Key.
     */
    public function discordConnect(Request $request, DiscordPlatform $discord)
    {
        $team = auth()->user()->currentTeam;
        if ($team && ! EnforcePlanLimits::canConnectPage($team)) {
            return redirect()->route('connections.index')
                ->with('error', 'You have reached your page limit. Please upgrade your plan to connect more pages.');
        }

        $request->validate([
            'bot_token'      => 'required|string|max:255',
            'application_id' => 'required|string|max:64',
            'public_key'     => 'required|string|max:128',
        ]);

        try {
            $teamId = auth()->user()->current_team_id;
            $account = $discord->handleCallback($request, $teamId);
            $team = auth()->user()->currentTeam;
            $team?->clearActivePagesCache();
            if ($team) $this->maybePromptAiSetup($team);
            return redirect()->route('connections.index')
                ->with('success', "Connected Discord bot: {$account->name}");
        } catch (\Throwable $e) {
            Log::error('Discord connection failed', ['error' => $e->getMessage()]);
            return redirect()->route('connections.index')
                ->with('error', 'Failed to connect Discord: ' . $e->getMessage());
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

            $team = auth()->user()->currentTeam;
            $team?->clearActivePagesCache();
            if ($team) $this->maybePromptAiSetup($team);

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

            $team = auth()->user()->currentTeam;
            $team?->clearActivePagesCache();
            if ($team) $this->maybePromptAiSetup($team);

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

            $team = auth()->user()->currentTeam;
            $team?->clearActivePagesCache();
            if ($team) $this->maybePromptAiSetup($team);

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

    /**
     * Flash the AI setup prompt the first time a team connects any page.
     * Uses team.settings['ai_setup_prompted'] so it only ever shows once,
     * even if the user disconnects and reconnects.
     */
    private function maybePromptAiSetup(Team $team): void
    {
        $settings = $team->settings ?? [];

        if (! empty($settings['ai_setup_prompted'])) {
            return;
        }

        $settings['ai_setup_prompted'] = true;
        $team->update(['settings' => $settings]);

        session()->flash('show_ai_setup_prompt', true);
    }
}
