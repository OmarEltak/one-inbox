<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Public-facing endpoints for the embeddable web-chat widget.
 *
 * No auth: each widget is identified by a 23-char widget_id that's randomly minted
 * when the team connects the widget on the Connections page. The widget_id is
 * stored in the embed snippet they paste on their site (so it's already public);
 * abuse is bounded by the throttle middleware in routes/api.php.
 *
 * Visitor identity:
 *   The widget mints a visitor_id on first load (UUID), persists it in
 *   localStorage, and sends it on every subsequent request. That id is the
 *   conversation key — same visitor returning later sees the same thread.
 */
class WebChatController extends Controller
{
    /**
     * Mint or echo back a visitor_id and return widget config + conversation history.
     */
    public function visitor(Request $request, string $widget): JsonResponse
    {
        $page = $this->resolveWidget($widget);
        if (! $page) {
            return response()->json(['error' => 'widget_not_found'], 404);
        }

        $visitorId = $request->input('visitor_id') ?: ('v_' . Str::lower(Str::random(24)));

        $conversation = Conversation::where('page_id', $page->id)
            ->where('platform_conversation_id', $visitorId)
            ->first();

        $history = [];
        if ($conversation) {
            $history = $conversation->messages()
                ->orderBy('id')
                ->limit(50)
                ->get(['id', 'direction', 'content', 'created_at'])
                ->map(fn ($m) => $this->serializeMessage($m))
                ->all();
        }

        return response()->json([
            'visitor_id'   => $visitorId,
            'history'      => $history,
            'theme_color'  => $page->metadata['theme_color'] ?? '#22c55e',
            'greeting'     => $page->metadata['greeting'] ?? 'Hi! How can we help?',
            'widget_name'  => $page->name,
        ]);
    }

    /**
     * Visitor sends a message. Persists it as an inbound Message and returns the new id.
     */
    public function send(Request $request, string $widget): JsonResponse
    {
        $data = $request->validate([
            'visitor_id' => 'required|string|max:64',
            'content'    => 'required|string|max:4000',
            'name'       => 'nullable|string|max:120',
            'email'      => 'nullable|email|max:255',
        ]);

        $page = $this->resolveWidget($widget);
        if (! $page) {
            return response()->json(['error' => 'widget_not_found'], 404);
        }

        $conversation = Conversation::firstOrCreate(
            [
                'page_id'                  => $page->id,
                'platform_conversation_id' => $data['visitor_id'],
            ],
            [
                'team_id'              => $page->team_id,
                'platform'             => 'webchat',
                'status'               => 'open',
                'last_message_at'      => now(),
                'last_message_preview' => Str::limit($data['content'], 80),
                'unread_count'         => 0,
                'metadata'             => [
                    'visitor_name'  => $data['name'] ?? null,
                    'visitor_email' => $data['email'] ?? null,
                    'first_url'     => $request->header('Referer'),
                ],
            ]
        );

        $message = Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => 'wc_in_' . Str::random(12),
            'direction'           => 'inbound',
            'sender_type'         => 'external',
            'sender_id'           => $data['visitor_id'],
            'content_type'        => 'text',
            'content'             => $data['content'],
            'platform_sent_at'    => now(),
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => Str::limit($data['content'], 80),
            'unread_count'         => $conversation->unread_count + 1,
        ]);

        return response()->json([
            'id'         => $message->id,
            'created_at' => $message->created_at->toIso8601String(),
        ]);
    }

    /**
     * Visitor polls for new messages on their conversation.
     * Pass ?since=<unix-ms> to only get newer messages; defaults to last 30s.
     */
    public function poll(Request $request, string $widget): JsonResponse
    {
        $visitorId = (string) $request->query('visitor_id', '');
        if ($visitorId === '') {
            return response()->json(['error' => 'visitor_id_required'], 422);
        }

        $page = $this->resolveWidget($widget);
        if (! $page) {
            return response()->json(['error' => 'widget_not_found'], 404);
        }

        $conversation = Conversation::where('page_id', $page->id)
            ->where('platform_conversation_id', $visitorId)
            ->first();

        if (! $conversation) {
            return response()->json(['messages' => []]);
        }

        $sinceMs = (int) $request->query('since', 0);
        $since = $sinceMs > 0 ? Carbon::createFromTimestampMs($sinceMs) : now()->subSeconds(30);

        $messages = $conversation->messages()
            ->where('created_at', '>', $since)
            ->orderBy('id')
            ->limit(50)
            ->get(['id', 'direction', 'content', 'created_at']);

        return response()->json([
            'messages' => $messages->map(fn ($m) => $this->serializeMessage($m))->all(),
        ]);
    }

    /**
     * Public widget config — used by widget.js on first paint to apply branding
     * before any visitor interaction. No conversation data here.
     */
    public function config(string $widget): JsonResponse
    {
        $page = $this->resolveWidget($widget);
        if (! $page) {
            return response()->json(['error' => 'widget_not_found'], 404);
        }

        return response()->json([
            'theme_color' => $page->metadata['theme_color'] ?? '#22c55e',
            'greeting'    => $page->metadata['greeting'] ?? 'Hi! How can we help?',
            'widget_name' => $page->name,
        ]);
    }

    private function resolveWidget(string $widgetId): ?Page
    {
        return Page::where('platform', 'webchat')
            ->where('platform_page_id', $widgetId)
            ->where('is_active', true)
            ->first();
    }

    private function serializeMessage(Message $m): array
    {
        return [
            'id'         => $m->id,
            'direction'  => $m->direction,
            'content'    => $m->content,
            'created_at' => $m->created_at->toIso8601String(),
            'created_at_ms' => (int) ($m->created_at->getTimestamp() * 1000 + ($m->created_at->micro / 1000)),
        ];
    }
}
