<?php

declare(strict_types=1);

namespace App\Services\Platforms;

use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * WebChat — first-party widget channel.
 *
 * Unlike Meta/Telegram/WhatsApp this isn't an OAuth/token integration: each "connection"
 * is a widget identifier the team embeds on their own site via a <script> tag. The
 * widget_id == Page.platform_page_id; visitors are conversations keyed by an opaque
 * visitor_id stored in the visitor's localStorage.
 *
 * Why this still uses ConnectedAccount + Page:
 *   - Reuses every existing inbox/conversation/message join — agents see webchat
 *     conversations identically to WhatsApp/Telegram ones.
 *   - SendPlatformMessage and ProcessIncomingMessage already branch by platform string;
 *     adding 'webchat' to those match()es is a 2-line change each.
 */
class WebChatPlatform extends AbstractPlatform
{
    public function getConnectUrl(): string
    {
        return '';
    }

    /**
     * "Connect" a webchat widget for the given team. No external API call —
     * we just mint a widget_id and persist a Page row.
     */
    public function handleCallback(Request $request, int $teamId): ConnectedAccount
    {
        $widgetName = trim((string) $request->input('widget_name', 'My Website'));
        if ($widgetName === '') {
            $widgetName = 'My Website';
        }

        $widgetId = 'wc_' . Str::lower(Str::random(20));

        $account = ConnectedAccount::updateOrCreate(
            [
                'team_id' => $teamId,
                'platform' => 'webchat',
                'platform_user_id' => $widgetId,
            ],
            [
                'name' => $widgetName,
                'access_token' => $widgetId,
                'is_active' => true,
                'connected_at' => now(),
                'metadata' => [
                    'widget_id' => $widgetId,
                ],
            ]
        );

        Page::updateOrCreate(
            [
                'team_id' => $teamId,
                'platform' => 'webchat',
                'platform_page_id' => $widgetId,
            ],
            [
                'connected_account_id' => $account->id,
                'name' => $widgetName,
                'page_access_token' => $widgetId,
                'is_active' => true,
                'metadata' => [
                    'widget_id' => $widgetId,
                    'theme_color' => $request->input('theme_color', '#22c55e'),
                    'greeting' => $request->input('greeting', 'Hi! How can we help?'),
                ],
            ]
        );

        return $account;
    }

    /**
     * Outbound from agent: persist the Message and let the widget poll pick it up.
     * Mirrors how the inbox already creates outbound messages elsewhere; this
     * method exists to satisfy the interface contract.
     */
    public function sendMessage(Page $page, Conversation $conversation, string $content, string $contentType = 'text', ?array $media = null): Message
    {
        return Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => 'wc_out_' . Str::random(12),
            'direction'           => 'outbound',
            'sender_type'         => 'agent',
            'content_type'        => $contentType,
            'content'             => $content,
            'media_url'           => $media['url'] ?? null,
            'media_type'          => $media['type'] ?? null,
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
        return $account->pages()->where('platform', 'webchat')->get();
    }

    public function handleWebhook(Request $request): void
    {
        // Visitor inbound is handled directly by WebChatController, not via webhook log.
    }

    public function verifyWebhook(Request $request): mixed
    {
        return null;
    }

    public function disconnect(ConnectedAccount $account): void
    {
        $account->pages()->where('platform', 'webchat')->update(['is_active' => false]);
        $account->update(['is_active' => false]);
    }
}
