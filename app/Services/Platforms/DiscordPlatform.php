<?php

declare(strict_types=1);

namespace App\Services\Platforms;

use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Discord — HTTP-only integration via the Interactions endpoint + REST API.
 *
 * Why HTTP-only and not the Gateway:
 *   Discord's Gateway (WebSocket) is the only way to receive plain message
 *   events. That requires a long-running process holding a connection 24/7,
 *   which doesn't fit a stateless Laravel deployment. Interactions
 *   (slash commands + modal submits) are pushed over HTTP and signed with
 *   Ed25519 — perfect for serverless-style apps.
 *
 * UX:
 *   In any channel where the bot is present (or in DM with the bot), the user
 *   types `/support`. Discord opens a modal, user submits text, the message
 *   lands in the inbox. Agent replies in the inbox → bot DMs the user back.
 *
 * Connection prerequisites (customer side):
 *   1. Create an Application at https://discord.com/developers/applications
 *   2. Add a Bot user; copy the Bot Token, Application ID, and Public Key.
 *   3. Set Interactions Endpoint URL = our /api/webhooks/discord (Discord
 *      will probe with a PING that we validate with the Public Key).
 *   4. Invite the bot to their server with `applications.commands` scope.
 *
 * On connect we register the global `/support` slash command via REST so
 * the customer doesn't have to do it manually.
 */
class DiscordPlatform extends AbstractPlatform
{
    protected string $apiBase = 'https://discord.com/api/v10';

    public function getConnectUrl(): string
    {
        return '';
    }

    public function handleCallback(Request $request, int $teamId): ConnectedAccount
    {
        $botToken      = trim((string) $request->input('bot_token'));
        $applicationId = trim((string) $request->input('application_id'));
        $publicKey     = trim((string) $request->input('public_key'));

        if ($botToken === '' || $applicationId === '' || $publicKey === '') {
            throw new \RuntimeException('Bot token, application ID, and public key are all required.');
        }

        // Validate the bot token by fetching @me.
        $me = Http::withHeaders(['Authorization' => 'Bot ' . $botToken])
            ->get("{$this->apiBase}/users/@me");

        if (! $me->successful()) {
            throw new \RuntimeException('Discord bot token validation failed: ' . $me->body());
        }

        $botUser = $me->json();
        $botName = ($botUser['username'] ?? 'Discord Bot') . '#' . ($botUser['discriminator'] ?? '0000');
        $botId   = (string) ($botUser['id'] ?? '');

        $account = ConnectedAccount::updateOrCreate(
            [
                'team_id'          => $teamId,
                'platform'         => 'discord',
                'platform_user_id' => $applicationId,
            ],
            [
                'name'         => $botName,
                'access_token' => $botToken,
                'is_active'    => true,
                'connected_at' => now(),
                'metadata'     => [
                    'bot_user_id'    => $botId,
                    'application_id' => $applicationId,
                    'public_key'     => $publicKey,
                ],
            ]
        );

        Page::updateOrCreate(
            [
                'team_id'          => $teamId,
                'platform'         => 'discord',
                'platform_page_id' => $applicationId,
            ],
            [
                'connected_account_id' => $account->id,
                'name'                 => $botName,
                'page_access_token'    => $botToken,
                'category'             => 'discord_app',
                'is_active'            => true,
                'metadata'             => [
                    'bot_user_id'    => $botId,
                    'application_id' => $applicationId,
                    'public_key'     => $publicKey,
                ],
            ]
        );

        // Register the /support slash command globally so it works in any guild
        // the bot is invited to as well as in DM.
        $this->registerSupportCommand($applicationId, $botToken);

        return $account;
    }

    /**
     * Register a global `/support` slash command. Idempotent — calling repeatedly
     * just updates the existing command.
     */
    protected function registerSupportCommand(string $applicationId, string $botToken): void
    {
        try {
            $resp = Http::withHeaders(['Authorization' => 'Bot ' . $botToken])
                ->post("{$this->apiBase}/applications/{$applicationId}/commands", [
                    'name'        => 'support',
                    'description' => 'Send a message to our support team',
                    'type'        => 1, // CHAT_INPUT
                    'options'     => [[
                        'name'        => 'message',
                        'description' => 'What can we help with?',
                        'type'        => 3, // STRING
                        'required'    => true,
                    ]],
                    'integration_types' => [0, 1], // GUILD_INSTALL + USER_INSTALL
                    'contexts'          => [0, 1, 2], // GUILD + BOT_DM + PRIVATE_CHANNEL
                ]);

            if (! $resp->successful()) {
                Log::warning('Discord /support command registration failed', ['body' => $resp->body()]);
            }
        } catch (\Throwable $e) {
            Log::warning('Discord /support command registration error', ['error' => $e->getMessage()]);
        }
    }

    public function sendMessage(Page $page, Conversation $conversation, string $content, string $contentType = 'text', ?array $media = null): Message
    {
        // The actual REST send lives in SendPlatformMessage::sendViaDiscord.
        // This contract method just persists the outbound row for callers that
        // bypass the queue (e.g. internal tooling).
        return Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => null,
            'direction'           => 'outbound',
            'sender_type'         => 'agent',
            'content_type'        => $contentType,
            'content'             => $content,
            'media_url'           => $media['url'] ?? null,
            'platform_sent_at'    => now(),
        ]);
    }

    public function fetchConversations(Page $page): Collection
    {
        return collect();
    }

    public function fetchMessages(Page $page, string $platformConversationId): Collection
    {
        return collect();
    }

    public function fetchPages(ConnectedAccount $account): Collection
    {
        return $account->pages()->where('platform', 'discord')->get();
    }

    public function handleWebhook(Request $request): void
    {
        // Handled by DiscordInteractionController.
    }

    public function verifyWebhook(Request $request): mixed
    {
        return null;
    }

    public function disconnect(ConnectedAccount $account): void
    {
        $account->pages()->where('platform', 'discord')->update(['is_active' => false]);
        $account->update(['is_active' => false]);
    }
}
