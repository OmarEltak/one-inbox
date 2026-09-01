<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Message;
use App\Services\Ai\VisionRouter;
use App\Services\Media\MediaStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DescribeImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    private const PROMPT = 'Describe this image for a customer-service AI. Note any product, defect, receipt, screen, text visible, or any signal about what the customer is asking. Keep under 120 words.';

    public function __construct(public int $messageId)
    {
        $this->onQueue('default');
    }

    public function handle(VisionRouter $router, MediaStorage $storage): void
    {
        $message = Message::with(['mediaAsset', 'conversation.page.team'])->find($this->messageId);
        if ($message === null || $message->mediaAsset === null) {
            return;
        }

        $model = $router->firstVisionCapableModel();
        if ($model === null) {
            Log::warning('No vision-capable model in NaraRouter chain', [
                'chain' => config('services.nararouter.fallback_models'),
            ]);
            SendAiResponse::dispatch($message->conversation_id, $message->id);
            return;
        }

        $imageUrl = $storage->streamUrl($message->mediaAsset);
        $payload  = $router->buildPayload($model, $imageUrl, self::PROMPT);

        try {
            $response = Http::withToken((string) config('services.nararouter.api_key'))
                ->timeout(20)
                ->post(rtrim((string) config('services.nararouter.base_url'), '/').'/chat/completions', $payload)
                ->throw()
                ->json();

            $description = $response['choices'][0]['message']['content'] ?? null;

            if (is_string($description) && $description !== '') {
                $meta = $message->mediaAsset->metadata ?? [];
                $meta['ai_description'] = $description;
                $message->mediaAsset->update(['metadata' => $meta]);
            }
        } catch (\Throwable $e) {
            Log::warning('Vision call failed', ['error' => $e->getMessage(), 'model' => $model]);
        }

        SendAiResponse::dispatch($message->conversation_id, $message->id);
    }
}
