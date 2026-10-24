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
    public function resolve(Page $page, string $platformPostId): PagesPost
    {
        $existing = PagesPost::where('page_id', $page->id)
            ->where('platform_post_id', $platformPostId)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Fall back to now() if the Graph fetch fails — the "future_only + enabled_at"
        // filter in CommentFilterService treats this as "post exists today → allow"
        // which is the safe default for an unknown-age post. Previously null was
        // returned here and IngestCommentJob aborted, silently dropping every IG
        // comment because Meta's IG media API uses `timestamp` (not `created_time`).
        $createdAt = $this->fetchCreatedTime($page, $platformPostId) ?? now();

        return PagesPost::create([
            'page_id'             => $page->id,
            'platform_post_id'    => $platformPostId,
            'created_at_platform' => $createdAt,
            'first_seen_at'       => now(),
        ]);
    }

    protected function fetchCreatedTime(Page $page, string $platformPostId): ?Carbon
    {
        // Meta's post-metadata endpoints disagree on field name:
        //   • FB page posts expose `created_time`
        //   • IG media objects expose `timestamp`
        // Asking for the wrong field returns HTTP 400 (#100) and the whole call fails.
        // Also, direct IG Business Login uses graph.instagram.com; via-FB IG + FB page
        // use graph.facebook.com.
        $isDirectIg   = $page->platform === 'instagram_business';
        $isInstagram  = $isDirectIg || $page->platform === 'instagram';
        $host         = $isDirectIg ? 'https://graph.instagram.com' : 'https://graph.facebook.com';
        $field        = $isInstagram ? 'timestamp' : 'created_time';

        try {
            $response = Http::timeout(10)->get("{$host}/v21.0/{$platformPostId}", [
                'fields'       => $field,
                'access_token' => $page->page_access_token,
            ]);

            if (! $response->successful()) {
                Log::warning('PostCreationTimeCache fetch failed — using now() fallback', [
                    'page_id'          => $page->id,
                    'platform'         => $page->platform,
                    'platform_post_id' => $platformPostId,
                    'field_requested'  => $field,
                    'status'           => $response->status(),
                    'body'             => $response->body(),
                ]);

                return null;
            }

            $raw = $response->json($field);

            return $raw ? Carbon::parse($raw) : null;
        } catch (\Throwable $e) {
            Log::warning('PostCreationTimeCache exception', [
                'page_id'          => $page->id,
                'platform'         => $page->platform,
                'platform_post_id' => $platformPostId,
                'error'            => $e->getMessage(),
            ]);

            return null;
        }
    }
}
