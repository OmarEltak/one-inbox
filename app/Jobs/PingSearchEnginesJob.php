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

        foreach ($this->urls as $url) {
            try {
                $google->notify($url, 'URL_UPDATED');
            } catch (Throwable $e) {
                Log::warning('Google Indexing ping threw', ['url' => $url, 'error' => $e->getMessage()]);
            }
        }
    }
}
