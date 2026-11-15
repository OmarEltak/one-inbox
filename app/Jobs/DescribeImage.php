<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Message;
use App\Models\ProductMedia;
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

    // Cap how many product images we attach to the visual-match call to keep
    // the vision payload manageable. 6 images × ~0.5MB = ~3MB over the wire,
    // which NaraRouter + the model can handle comfortably. Beyond this, latency
    // and cost rise sharply with diminishing match-quality returns.
    private const MAX_PRODUCT_IMAGES_FOR_MATCH = 6;

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

        // Second pass: visual product match. Text-overlap matching (vision
        // description vs product name) fails when the product's uploaded
        // images don't look like the product name — e.g. an "online course"
        // product whose media are technical screenshots. Giving the vision
        // model both the customer's image AND our product images lets it
        // decide visually which product (if any) the customer meant.
        $this->resolveProductMatch($router, $message, $model, $imageUrl);

        SendAiResponse::dispatch($message->conversation_id, $message->id);
    }

    /**
     * Visual product match. Sends the customer's image plus up to N product
     * primary images to the vision model and asks which product_id it matches.
     * Result (or 'none') stored on mediaAsset.metadata.product_match_id —
     * AiMediaResolver reads this ahead of its text-overlap fallback.
     *
     * Non-fatal: any failure (no products, no media, vision 500) leaves the
     * previous metadata intact and silently returns. The caller (handle) still
     * dispatches SendAiResponse so the AI reply flow is never blocked.
     */
    private function resolveProductMatch(VisionRouter $router, Message $message, string $model, string $customerImageUrl): void
    {
        $aiConfig = $message->conversation?->page?->aiConfig;
        if (! $aiConfig) {
            return;
        }

        // Pull one representative image per product (primary preferred, else
        // oldest by display_order). We don't need every variant — a single
        // reference per product is enough for the model to visually identify
        // the match.
        $productMedia = ProductMedia::where('ai_config_id', $aiConfig->id)
            ->where('allow_ai_autosend', true)
            ->orderByDesc('is_primary')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->groupBy('product_key')
            ->map(fn ($group) => $group->first())
            ->values()
            ->take(self::MAX_PRODUCT_IMAGES_FOR_MATCH);

        if ($productMedia->isEmpty()) {
            return;
        }

        $catalog = collect((array) ($aiConfig->product_catalog ?? []))
            ->keyBy('id');

        $contentParts = [];
        $contentParts[] = [
            'type' => 'text',
            'text' => $this->buildMatchPrompt($productMedia, $catalog),
        ];
        $contentParts[] = [
            'type'      => 'image_url',
            'image_url' => ['url' => $customerImageUrl],
        ];
        foreach ($productMedia as $pm) {
            $contentParts[] = [
                'type'      => 'image_url',
                'image_url' => ['url' => $pm->url()],
            ];
        }

        try {
            $response = Http::withToken((string) config('services.nararouter.api_key'))
                ->timeout(25)
                ->post(rtrim((string) config('services.nararouter.base_url'), '/').'/chat/completions', [
                    'model'    => $model,
                    'messages' => [['role' => 'user', 'content' => $contentParts]],
                    'max_tokens' => 60,
                ])
                ->throw()
                ->json();

            $reply = trim((string) ($response['choices'][0]['message']['content'] ?? ''));
            if ($reply === '') {
                return;
            }

            // Accept any product_id uuid substring; tolerate the model wrapping
            // it in prose ("The match is <uuid>." etc). Match uuid v4 shape.
            $matchedId = null;
            if (preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $reply, $m)) {
                $candidate = strtolower($m[0]);
                if ($productMedia->pluck('product_key')->contains($candidate)) {
                    $matchedId = $candidate;
                }
            }

            $meta = $message->mediaAsset->metadata ?? [];
            $meta['product_match_id']    = $matchedId; // null when the model said NONE
            $meta['product_match_reply'] = mb_substr($reply, 0, 200);
            $message->mediaAsset->update(['metadata' => $meta]);
        } catch (\Throwable $e) {
            Log::warning('Product match vision call failed', [
                'error'      => $e->getMessage(),
                'message_id' => $message->id,
            ]);
        }
    }

    private function buildMatchPrompt(\Illuminate\Support\Collection $productMedia, \Illuminate\Support\Collection $catalog): string
    {
        $lines = [];
        foreach ($productMedia as $i => $pm) {
            $name = trim((string) ($catalog->get($pm->product_key)['name'] ?? ''));
            if ($name === '') {
                $name = 'Product ' . substr($pm->product_key, 0, 8);
            }
            // Image index starts at 2 because image 1 is the customer's.
            $lines[] = sprintf('  Image %d: product_id=%s ("%s")', $i + 2, $pm->product_key, $name);
        }

        return "You are matching a customer's image to a product catalog.\n\n"
            . "IMAGE 1 is the customer's image.\n"
            . "IMAGES 2+ are reference images from our product catalog:\n"
            . implode("\n", $lines) . "\n\n"
            . "Decide whether IMAGE 1 shows (or is a crop/screenshot/photo of) any of the catalog images. "
            . "Compare visually — same scene, same screenshot, same object, same product even from a different angle.\n\n"
            . "Respond with ONLY ONE of the following, nothing else:\n"
            . "  • The product_id (the uuid after 'product_id=') of the best match, OR\n"
            . "  • The literal word NONE if no catalog image matches.\n\n"
            . "Do NOT invent a product_id. Do NOT explain. Just the uuid or NONE.";
    }
}
