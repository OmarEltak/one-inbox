<?php

namespace App\Jobs;

use App\Contracts\AiProviderInterface;
use App\Events\AiResponseSent;
use App\Http\Middleware\EnforcePlanLimits;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendAiResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 10;

    public function __construct(
        public int $conversationId,
        public int $triggerMessageId
    ) {}

    public function handle(AiProviderInterface $ai): void
    {
        $conversation = Conversation::with(['page.team', 'page.aiConfig', 'contact'])->find($this->conversationId);

        if (! $conversation) {
            return;
        }

        $team = $conversation->page->team;

        // Double-check AI is still enabled (could have been turned off since dispatch)
        if (! $team->isAiEnabled()) {
            return;
        }

        // Page was disconnected after this job was queued
        if (! $conversation->page->is_active) {
            return;
        }

        $aiConfig = $conversation->page->aiConfig;

        if (! $aiConfig || ! $aiConfig->is_active) {
            return;
        }

        if (! $aiConfig->isWithinWorkingHours()) {
            return;
        }

        // Human agent has taken over this conversation
        if ($conversation->ai_paused) {
            return;
        }

        // Check AI credit limits
        if (! EnforcePlanLimits::hasAiCredits($team)) {
            Log::info("Team {$team->id} has exhausted AI credits, skipping AI response");
            return;
        }

        $triggerMessage = Message::find($this->triggerMessageId);

        if (! $triggerMessage) {
            return;
        }

        // Check if a human has already responded since the trigger message
        $humanReplied = $conversation->messages()
            ->where('id', '>', $this->triggerMessageId)
            ->where('sender_type', 'user')
            ->exists();

        if ($humanReplied) {
            return; // Human took over, don't send AI response
        }

        if ($this->shouldEscalate($triggerMessage->content ?? '')) {
            $labels = $conversation->labels ?? [];
            if (! in_array('escalated', $labels)) {
                $labels[] = 'escalated';
                $conversation->update(['labels' => $labels]);
            }
            return;
        }

        try {
            $responseText = $ai->generateResponse($conversation, $triggerMessage, $aiConfig);

            if (empty($responseText)) {
                return;
            }

            // Store the AI message in our database
            $aiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'direction' => 'outbound',
                'sender_type' => 'ai',
                'content_type' => 'text',
                'content' => $responseText,
            ]);

            // Send through the platform
            $this->sendToPlatform($conversation, $responseText, $aiMessage);

            $conversation->update([
                'last_message_at' => now(),
                'last_message_preview' => \Illuminate\Support\Str::limit($responseText, 100),
            ]);

            // Increment AI credits used
            $team->increment('ai_credits_used');

            // Broadcast real-time update
            broadcast(AiResponseSent::fromMessage($aiMessage, $conversation));
        } catch (\Throwable $e) {
            Log::error("AI response failed for conversation {$this->conversationId}", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function shouldEscalate(string $messageContent): bool
    {
        $keywords = [
            'talk to a human',
            'talk to human',
            'real person',
            'speak to someone',
            'speak to a person',
            'manager',
            'supervisor',
            'agent',
            'representative',
            'human please',
            'real agent',
            'live agent',
            'talk to someone',
            'speak with a human',
        ];

        $lower = mb_strtolower($messageContent);

        foreach ($keywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function sendToPlatform(Conversation $conversation, string $text, Message $aiMessage): void
    {
        $page = $conversation->page;
        $platform = $conversation->platform;
        $recipientId = $conversation->platform_conversation_id;

        match ($platform) {
            'facebook', 'instagram' => $this->sendViaMetaMessenger($page, $recipientId, $text, $aiMessage),
            'whatsapp' => $this->sendViaWhatsApp($page, $recipientId, $text, $aiMessage),
            'telegram' => $this->sendViaTelegram($page, $recipientId, $text, $aiMessage),
            default => Log::warning("Cannot send to unknown platform: {$platform}"),
        };
    }

    protected function sendViaMetaMessenger(Page $page, string $recipientId, string $text, Message $aiMessage): void
    {
        $version = config('services.meta.graph_api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$page->platform_page_id}/messages";

        $response = Http::withToken($page->page_access_token)->post($url, [
            'recipient' => ['id' => $recipientId],
            'messaging_type' => 'RESPONSE',
            'message' => ['text' => $text],
        ]);

        if ($response->successful()) {
            $aiMessage->update([
                'platform_message_id' => $response->json('message_id'),
                'platform_sent_at' => now(),
            ]);
        } else {
            Log::error('Meta Messenger send failed', ['body' => $response->body()]);
        }
    }

    protected function sendViaWhatsApp(Page $page, string $recipientId, string $text, Message $aiMessage): void
    {
        $version = config('services.meta.graph_api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$page->platform_page_id}/messages";

        $response = Http::withToken($page->page_access_token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to' => $recipientId,
            'type' => 'text',
            'text' => ['body' => $text],
        ]);

        if ($response->successful()) {
            $messageId = $response->json('messages.0.id');
            $aiMessage->update([
                'platform_message_id' => $messageId,
                'platform_sent_at' => now(),
            ]);
        } else {
            Log::error('WhatsApp send failed', ['body' => $response->body()]);
        }
    }

    protected function sendViaTelegram(Page $page, string $chatId, string $text, Message $aiMessage): void
    {
        $botToken = $page->page_access_token; // For Telegram, we store bot token here

        $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        if ($response->successful()) {
            $aiMessage->update([
                'platform_message_id' => (string) $response->json('result.message_id'),
                'platform_sent_at' => now(),
            ]);
        } else {
            Log::error('Telegram send failed', ['body' => $response->body()]);
        }
    }
}
