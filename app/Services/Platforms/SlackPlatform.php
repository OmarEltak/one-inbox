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
 * Slack — Events API + Web API.
 *
 * Connection flow:
 *   1. Customer creates a Slack App at https://api.slack.com/apps.
 *   2. Adds bot scopes: chat:write, channels:history, groups:history, im:history,
 *      mpim:history, channels:read, users:read.
 *   3. Installs to workspace; copies Bot User OAuth Token (xoxb-…) and Signing Secret.
 *   4. Sets Event Subscriptions URL → /api/webhooks/slack and subscribes to bot
 *      events: message.channels, message.groups, message.im.
 *   5. Pastes the two values into our Connections modal; we validate via auth.test.
 *
 * Inbox model:
 *   Page.platform_page_id  = team_id (Slack workspace ID)
 *   Page.page_access_token = bot OAuth token
 *   Page.metadata.signing_secret = used to verify inbound event signatures
 *   Page.metadata.bot_user_id    = used to filter out our own bot's echoes
 *   Conversation.platform_conversation_id = channel_id (DM, group, or channel)
 */
class SlackPlatform extends AbstractPlatform
{
    protected string $apiBase = 'https://slack.com/api';

    public function getConnectUrl(): string
    {
        return '';
    }

    /**
     * Validate the bot token and persist the workspace as a Page.
     */
    public function handleCallback(Request $request, int $teamId): ConnectedAccount
    {
        $botToken      = trim((string) $request->input('bot_token'));
        $signingSecret = trim((string) $request->input('signing_secret'));

        if (! str_starts_with($botToken, 'xoxb-')) {
            throw new \RuntimeException('Slack bot token must start with "xoxb-".');
        }
        if ($signingSecret === '') {
            throw new \RuntimeException('Signing secret is required to verify Slack event signatures.');
        }

        $auth = Http::withToken($botToken)
            ->post("{$this->apiBase}/auth.test")
            ->json();

        if (! ($auth['ok'] ?? false)) {
            throw new \RuntimeException('Slack auth.test failed: ' . ($auth['error'] ?? 'unknown_error'));
        }

        $workspaceId   = (string) ($auth['team_id'] ?? '');
        $workspaceName = (string) ($auth['team'] ?? 'Slack Workspace');
        $botUserId     = (string) ($auth['user_id'] ?? '');
        $botName       = (string) ($auth['user'] ?? 'Slack Bot');

        $account = ConnectedAccount::updateOrCreate(
            [
                'team_id'          => $teamId,
                'platform'         => 'slack',
                'platform_user_id' => $workspaceId,
            ],
            [
                'name'         => $workspaceName,
                'access_token' => $botToken,
                'is_active'    => true,
                'connected_at' => now(),
                'metadata'     => [
                    'bot_user_id' => $botUserId,
                    'bot_name'    => $botName,
                ],
            ]
        );

        Page::updateOrCreate(
            [
                'team_id'          => $teamId,
                'platform'         => 'slack',
                'platform_page_id' => $workspaceId,
            ],
            [
                'connected_account_id' => $account->id,
                'name'                 => $workspaceName,
                'page_access_token'    => $botToken,
                'category'             => 'slack_workspace',
                'is_active'            => true,
                'metadata'             => [
                    'signing_secret' => $signingSecret,
                    'bot_user_id'    => $botUserId,
                    'bot_name'       => $botName,
                ],
            ]
        );

        return $account;
    }

    public function sendMessage(Page $page, Conversation $conversation, string $content, string $contentType = 'text', ?array $media = null): Message
    {
        $channelId = $conversation->platform_conversation_id;
        $botToken  = $page->page_access_token;

        $response = Http::withToken($botToken)
            ->post("{$this->apiBase}/chat.postMessage", [
                'channel' => $channelId,
                'text'    => $content,
            ]);

        $body = $response->json();
        $platformMessageId = null;
        if (! empty($body['ok'])) {
            $platformMessageId = (string) ($body['ts'] ?? '');
        } else {
            Log::error('Slack chat.postMessage failed', ['body' => $body]);
        }

        $message = Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => $platformMessageId,
            'direction'           => 'outbound',
            'sender_type'         => 'agent',
            'sender_id'           => auth()->id(),
            'content_type'        => $contentType,
            'content'             => $content,
            'media_url'           => $media['url'] ?? null,
            'media_type'          => $contentType !== 'text' ? $contentType : null,
            'platform_sent_at'    => now(),
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => Str::limit($content, 100),
        ]);

        return $message;
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
        return $account->pages()->where('platform', 'slack')->get();
    }

    public function handleWebhook(Request $request): void
    {
        // Handled by SlackWebhookController.
    }

    public function verifyWebhook(Request $request): mixed
    {
        return null;
    }

    public function disconnect(ConnectedAccount $account): void
    {
        $account->pages()->where('platform', 'slack')->update(['is_active' => false]);
        $account->update(['is_active' => false]);
    }
}
