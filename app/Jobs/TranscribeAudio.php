<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Message;
use App\Services\Ai\TranscriptionRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class TranscribeAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 90;

    public function __construct(public int $messageId)
    {
        $this->onQueue('transcription');
    }

    public function handle(TranscriptionRouter $router): void
    {
        $message = Message::with('mediaAsset', 'conversation.page.team')->find($this->messageId);
        if ($message === null || $message->mediaAsset === null) {
            return;
        }

        $teamId = $message->conversation?->page?->team_id;
        $key    = "transcribe:inflight:{$teamId}";
        $limit  = 5;

        // Atomic per-team in-flight cap. 6th job releases back to the queue.
        if (Cache::add($key, 1, 300)) {
            $current = 1;
        } else {
            $current = Cache::increment($key);
        }
        if ($current > $limit) {
            Cache::decrement($key);
            $this->release(3);
            return;
        }

        try {
            $text = $router->transcribe($message->mediaAsset);

            if ($text === null) {
                $message->update(['content' => '[voice note — transcription unavailable]']);
                return;
            }

            $message->update(['content' => $text]);

            if ($message->conversation?->team?->canDispatchAi()) {
                SendAiResponse::dispatch($message->conversation_id, $message->id);
            }
        } finally {
            Cache::decrement($key);
        }
    }
}
