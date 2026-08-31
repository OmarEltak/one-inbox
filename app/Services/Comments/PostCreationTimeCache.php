<?php

declare(strict_types=1);

namespace App\Services\Comments;

use App\Models\Page;
use App\Models\PagesPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostCreationTimeCache
{
    public function resolve(Page $page, string $platformPostId): ?PagesPost
    {
        $existing = PagesPost::where('page_id', $page->id)
            ->where('platform_post_id', $platformPostId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $createdAt = $this->fetchCreatedTime($page, $platformPostId);
        if (! $createdAt) {
            return null;
        }

        return PagesPost::create([
            'page_id'             => $page->id,
            'platform_post_id'    => $platformPostId,
            'created_at_platform' => $createdAt,
            'first_seen_at'       => now(),
        ]);
    }

    protected function fetchCreatedTime(Page $page, string $platformPostId): ?Carbon
    {
        try {
            $response = Http::timeout(10)->get("https://graph.facebook.com/v21.0/{$platformPostId}", [
                'fields'       => 'created_time',
                'access_token' => decrypt($page->page_access_token),
            ]);

            if (! $response->successful()) {
                Log::warning('PostCreationTimeCache fetch failed', [
                    'page_id'          => $page->id,
                    'platform_post_id' => $platformPostId,
                    'status'           => $response->status(),
                    'body'             => $response->body(),
                ]);

                return null;
            }

            $createdTime = $response->json('created_time');

            return $createdTime ? Carbon::parse($createdTime) : null;
        } catch (\Throwable $e) {
            Log::warning('PostCreationTimeCache exception', [
                'page_id'          => $page->id,
                'platform_post_id' => $platformPostId,
                'error'            => $e->getMessage(),
            ]);

            return null;
        }
    }
}
