<?php

namespace App\Jobs;

use App\Contracts\AiProviderInterface;
use App\Models\Contact;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScoreLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public int $messageId,
        public int $contactId
    ) {}

    public function handle(AiProviderInterface $ai): void
    {
        $message = Message::find($this->messageId);
        $contact = Contact::find($this->contactId);

        if (! $message || ! $contact) {
            return;
        }

        // Only score inbound messages with text content
        if (! $message->isInbound() || ! $message->content) {
            return;
        }

        try {
            $events = $ai->scoreMessage($message, $contact);

            foreach ($events as $event) {
                if (! isset($event['event_type'], $event['score_change'], $event['reason'])) {
                    continue;
                }

                $contact->adjustScore(
                    change: (int) $event['score_change'],
                    eventType: $event['event_type'],
                    reason: $event['reason'],
                    conversationId: $message->conversation_id,
                    aiAnalysis: $event['ai_analysis'] ?? null,
                );
            }
        } catch (\Throwable $e) {
            Log::warning("Lead scoring failed for message {$this->messageId}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
