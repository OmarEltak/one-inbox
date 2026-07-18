<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Seo\GoogleIndexingService;
use App\Services\Seo\IndexNowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PingSearchEnginesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    /**
     * @param  array<int, string>  $urls
     */
    public function __construct(public readonly array $urls) {}

    public function handle(IndexNowService $indexNow, GoogleIndexingService $google): void
    {
        if ($this->urls === []) {
            return;
        }

        try {
            $indexNow->submit($this->urls);
        } catch (Throwable $e) {
            Log::warning('IndexNow ping threw', ['error' => $e->getMessage()]);
        }

        // Space Google Indexing calls 1s apart to stay well under Google's 60 req/min per-project cap.
        // Daily quota (200 URLs default) is not affected by pacing — request quota increase in Cloud Console if needed.
        $isFirst = true;
        foreach ($this->urls as $url) {
            if (! $isFirst) {
                usleep(1_100_000);
            }
            $isFirst = false;

            try {
                $google->notify($url, 'URL_UPDATED');
            } catch (Throwable $e) {
                Log::warning('Google Indexing ping threw', ['url' => $url, 'error' => $e->getMessage()]);
            }
        }
    }
}
