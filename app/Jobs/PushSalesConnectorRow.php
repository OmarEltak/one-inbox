<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Services\SalesConnectors\SalesConnectors;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * POSTs one sales row to the page's configured sheet / webhook URLs.
 * Single attempt on purpose: a retry would re-append to the target that
 * already succeeded (duplicate rows). Failures are shown in the tab instead.
 */
class PushSalesConnectorRow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public int $conversationId, public string $event) {}

    public function handle(): void
    {
        $conversation = Conversation::with(['contact', 'page.aiConfig'])->find($this->conversationId);
        $config = $conversation?->page?->aiConfig;
        if (! $config) {
            return;
        }

        $settings = (array) ($config->sales_connectors ?? []);
        $targets = array_filter([$settings['sheet_url'] ?? null, $settings['webhook_url'] ?? null]);
        if ($targets === []) {
            return;
        }

        $payload = SalesConnectors::payload($conversation, $this->event);
        $errors = [];

        foreach ($targets as $url) {
            $host = parse_url($url, PHP_URL_HOST) ?: 'connector';
            if (! SalesConnectors::isAllowedUrl($url)) {
                $errors[] = "{$host}: URL refused (must be a public https address)";

                continue;
            }

            try {
                $response = Http::timeout(20)->acceptJson()->post($url, $payload);
                if ($response->failed()) {
                    $errors[] = "{$host}: HTTP {$response->status()}";
                }
            } catch (\Throwable $e) {
                $errors[] = "{$host}: " . Str::limit($e->getMessage(), 140);
            }
        }

        SalesConnectors::recordDelivery($config, $this->event, $errors);
    }
}
