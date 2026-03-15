<?php

namespace App\Contracts;

use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface MessagingPlatformInterface
{
    /**
     * Send a message through the platform.
     */
    public function sendMessage(Page $page, Conversation $conversation, string $content, string $contentType = 'text', ?array $media = null): Message;

    /**
     * Fetch conversations from the platform.
     */
    public function fetchConversations(Page $page): Collection;

    /**
     * Fetch messages for a specific conversation.
     */
    public function fetchMessages(Page $page, string $platformConversationId): Collection;

    /**
     * Process an incoming webhook payload.
     */
    public function handleWebhook(Request $request): void;

    /**
     * Verify webhook signature/challenge.
     */
    public function verifyWebhook(Request $request): mixed;

    /**
     * Get the OAuth redirect URL for connecting an account.
     */
    public function getConnectUrl(): string;

    /**
     * Handle the OAuth callback and store the connected account.
     */
    public function handleCallback(Request $request, int $teamId): ConnectedAccount;

    /**
     * Disconnect an account and clean up.
     */
    public function disconnect(ConnectedAccount $account): void;

    /**
     * Fetch pages/channels for a connected account.
     */
    public function fetchPages(ConnectedAccount $account): Collection;
}
