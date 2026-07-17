<?php

declare(strict_types=1);

namespace App\Services\Seo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexNowService
{
    private const ENDPOINT = 'https://api.indexnow.org/indexnow';

    /**
     * Submit one or more URLs to IndexNow (Bing, Yandex, DuckDuckGo, Seznam).
     * Returns true on 200/202 (accepted) or 422 (already submitted recently).
     *
     * @param  array<int, string>  $urls
     */
    public function submit(array $urls): bool
    {
        $key  = config('services.indexnow.key');
        $host = config('services.indexnow.host');

        if (! $key || ! $host || $urls === []) {
            return false;
        }

        $payload = [
            'host'        => $host,
            'key'         => $key,
            'keyLocation' => "https://{$host}/{$key}.txt",
            'urlList'     => array_values($urls),
        ];

        $response = Http::timeout(10)
            ->acceptJson()
            ->asJson()
            ->post(self::ENDPOINT, $payload);

        if (in_array($response->status(), [200, 202], true)) {
            Log::info('IndexNow submitted', ['count' => count($urls), 'status' => $response->status()]);
            return true;
        }

        Log::warning('IndexNow submission failed', [
            'status' => $response->status(),
            'body'   => $response->body(),
            'urls'   => $urls,
        ]);
        return false;
    }
}
