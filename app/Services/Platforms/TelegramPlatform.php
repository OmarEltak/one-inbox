<?php

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

class TelegramPlatform extends AbstractPlatform
{
    protected string $apiBase = 'https://api.telegram.org';

    /**
     * Telegram doesn't use OAuth - uses bot token from BotFather.
     */
    public function getConnectUrl(): string
    {
        return '';
    }

    /**
     * Handle Telegram connection: validate bot token, set webhook, store as page.
     */
    public function handleCallback(Request $request, int $teamId): ConnectedAccount
    {
        $botToken = $request->input('bot_token');

        // Validate the bot token via getMe
        $response = Http::get("{$this->apiBase}/bot{$botToken}/getMe")
            ->throw()->json();

        $botInfo = $response['result'];

        // Create or update the connected account
        $account = ConnectedAccount::updateOrCreate(
            [
                'team_id' => $teamId,
                'platform' => 'telegram',
                'platform_user_id' => (string) $botInfo['id'],
            ],
            [
                'name' => $botInfo['first_name'] ?? 'Telegram Bot',
                'access_token' => $botToken,
                'scopes' => ['bot'],
                'is_active' => true,
                'connected_at' => now(),
                'metadata' => [
                    'username' => $botInfo['username'] ?? null,
                    'can_join_groups' => $botInfo['can_join_groups'] ?? false,
                    'can_read_all_group_messages' => $botInfo['can_read_all_group_messages'] ?? false,
                    'supports_inline_queries' => $botInfo['supports_inline_queries'] ?? false,
                ],
            ]
        );

        // Store bot as a "page"
        $page = Page::updateOrCreate(
            [
                'team_id' => $teamId,
                'platform' => 'telegram',
                'platform_page_id' => (string) $botInfo['id'],
            ],
            [
                'connected_account_id' => $account->id,
                'name' => $botInfo['first_name'] ?? 'Telegram Bot',
                'page_access_token' => $botToken,
                'category' => 'telegram_bot',
                'is_active' => true,
                'metadata' => [
                    'username' => $botInfo['username'] ?? null,
                ],
            ]
        );

        // Set webhook URL
        $this->setWebhook($botToken);

        return $account;
    }

    /**
     * Set the Telegram webhook URL for this bot.
     */
    protected function setWebhook(string $botToken): bool
    {
        $webhookUrl = url('/api/webhooks/telegram');
        $secret = config('services.telegram.webhook_secret');

        $params = [
            'url' => $webhookUrl,
            'allowed_updates' => ['message', 'edited_message', 'callback_query'],
        ];

        if ($secret) {
            $params['secret_token'] = $secret;
        }

        $response = Http::post("{$this->apiBase}/bot{$botToken}/setWebhook", $params);

        if ($response->failed()) {
            Log::error('Failed to set Telegram webhook', ['body' => $response->body()]);
            return false;
        }

        return $response->json('result', false);
    }

    /**
     * Send a message via Telegram Bot API.
     */
    public function sendMessage(Page $page, Conversation $conversation, string $content, string $contentType = 'text', ?array $media = null): Message
    {
        $chatId = $conversation->platform_conversation_id;
        $botToken = $page->page_access_token;

        if ($contentType === 'image' && $media) {
            $response = Http::post("{$this->apiBase}/bot{$botToken}/sendPhoto", [
                'chat_id' => $chatId,
                'photo' => $media['url'],
                'caption' => $content,
            ]);
        } else {
            $response = Http::post("{$this->apiBase}/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $content,
            ]);
        }

        $platformMessageId = null;
        if ($response->successful()) {
            $platformMessageId = (string) $response->json('result.message_id');
        } else {
            Log::error('Telegram send failed', ['body' => $response->body()]);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'platform_message_id' => $platformMessageId,
            'direction' => 'outbound',
            'sender_type' => 'user',
            'sender_id' => auth()->id(),
            'content_type' => $contentType,
            'content' => $content,
            'media_url' => $media['url'] ?? null,
            'media_type' => $contentType !== 'text' ? $contentType : null,
            'platform_sent_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => Str::limit($content, 100),
        ]);

        return $message;
    }

    /**
     * Telegram doesn't have a "fetch conversations" API.
     * Conversations are created from incoming messages.
     */
    public function fetchConversations(Page $page): Collection
    {
        return collect();
    }

    public function fetchMessages(Page $page, string $platformConversationId): Collection
    {
        return collect();
    }

    /**
     * For Telegram, each bot is a page. Return the single page.
     */
    public function fetchPages(ConnectedAccount $account): Collection
    {
        return $account->pages()->where('platform', 'telegram')->get();
    }

    public function handleWebhook(Request $request): void
    {
        // Handled by TelegramWebhookController + ProcessIncomingMessage job
    }

    public function verifyWebhook(Request $request): mixed
    {
        // Handled by TelegramWebhookController
        return null;
    }

    public function disconnect(ConnectedAccount $account): void
    {
        // Remove webhook
        $botToken = $account->access_token;
        Http::post("{$this->apiBase}/bot{$botToken}/deleteWebhook");

        $account->pages()->where('platform', 'telegram')->update(['is_active' => false]);
        $account->update(['is_active' => false]);
    }
}
