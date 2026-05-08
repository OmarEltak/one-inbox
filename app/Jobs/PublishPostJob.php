<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SocialPost;
use App\Models\SocialPostTarget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Fans out a SocialPost across every selected target.
 *
 * Each target is published independently — one platform failing never blocks
 * the others. The job updates each SocialPostTarget's status as it goes,
 * then rolls everything up into the parent post's status when done.
 *
 * Adding a new platform: add a `case` to the match() inside the loop and a
 * publishVia<Platform>() method below. Keep them all idempotent in spirit
 * (we don't retry per-target right now, but a future retry shouldn't double-post).
 */
class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public int $socialPostId) {}

    public function handle(): void
    {
        $post = SocialPost::with('targets.page')->find($this->socialPostId);
        if (! $post) {
            return;
        }

        $post->update(['status' => SocialPost::STATUS_PUBLISHING]);

        $mediaUrl = $this->resolveMediaPublicUrl($post->media_path);

        foreach ($post->targets as $target) {
            try {
                $target->markPublishing();

                $platformPostId = match ($target->platform) {
                    'facebook'  => $this->publishViaFacebook($target, $post, $mediaUrl),
                    'instagram' => $this->publishViaInstagram($target, $post, $mediaUrl),
                    'telegram'  => $this->publishViaTelegram($target, $post, $mediaUrl),
                    'slack'     => $this->publishViaSlack($target, $post),
                    'discord'   => $this->publishViaDiscord($target, $post, $mediaUrl),
                    default     => throw new \RuntimeException("Unsupported platform: {$target->platform}"),
                };

                $target->markSucceeded($platformPostId);
            } catch (\Throwable $e) {
                Log::error('PublishPostJob: target failed', [
                    'post'     => $post->id,
                    'target'   => $target->id,
                    'platform' => $target->platform,
                    'error'    => $e->getMessage(),
                ]);
                $target->markFailed($e->getMessage());
            }
        }

        $post->refresh()->recomputeStatus();
    }

    private function resolveMediaPublicUrl(?string $mediaPath): ?string
    {
        if (! $mediaPath) {
            return null;
        }
        // Stored on the public disk under social-posts/<file>; convert to absolute URL
        // because external platforms (FB/IG/Discord) need to fetch it themselves.
        try {
            return Storage::disk('public')->url($mediaPath);
        } catch (\Throwable $e) {
            Log::warning('Could not resolve media URL', ['path' => $mediaPath, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Facebook Page feed post.
     * Endpoint: POST /v21.0/{page-id}/feed (text) or /photos (image)
     */
    private function publishViaFacebook(SocialPostTarget $target, SocialPost $post, ?string $mediaUrl): string
    {
        $page = $target->page;
        $version = config('services.meta.graph_api_version', 'v21.0');
        $token = $page->page_access_token;
        $pageId = $page->platform_page_id;

        if ($mediaUrl) {
            $resp = Http::post("https://graph.facebook.com/{$version}/{$pageId}/photos", [
                'url'          => $mediaUrl,
                'caption'      => $post->content ?? '',
                'access_token' => $token,
            ]);
        } else {
            $resp = Http::post("https://graph.facebook.com/{$version}/{$pageId}/feed", [
                'message'      => $post->content ?? '',
                'access_token' => $token,
            ]);
        }

        if (! $resp->successful()) {
            throw new \RuntimeException('Facebook publish failed: ' . $resp->body());
        }
        return (string) ($resp->json('id') ?? $resp->json('post_id') ?? '');
    }

    /**
     * Instagram Business publish — 2-step: create media container, then publish.
     * IG REQUIRES media; text-only posts are not supported by the Graph API.
     */
    private function publishViaInstagram(SocialPostTarget $target, SocialPost $post, ?string $mediaUrl): string
    {
        if (! $mediaUrl) {
            throw new \RuntimeException('Instagram requires an image or video. Add media before publishing.');
        }

        $page = $target->page;
        $version = config('services.meta.graph_api_version', 'v21.0');
        $token = $page->page_access_token;
        $igUserId = $page->platform_page_id;

        $isInstagramBusiness = ($page->metadata['auth_type'] ?? null) === 'instagram_business';
        $base = $isInstagramBusiness ? 'https://graph.instagram.com' : 'https://graph.facebook.com';

        // Step 1: create media container
        $createResp = Http::post("{$base}/{$version}/{$igUserId}/media", [
            'image_url'    => $mediaUrl,
            'caption'      => $post->content ?? '',
            'access_token' => $token,
        ]);
        if (! $createResp->successful()) {
            throw new \RuntimeException('Instagram container create failed: ' . $createResp->body());
        }
        $containerId = $createResp->json('id');
        if (! $containerId) {
            throw new \RuntimeException('Instagram container missing id: ' . $createResp->body());
        }

        // Step 2: publish container
        $publishResp = Http::post("{$base}/{$version}/{$igUserId}/media_publish", [
            'creation_id'  => $containerId,
            'access_token' => $token,
        ]);
        if (! $publishResp->successful()) {
            throw new \RuntimeException('Instagram publish failed: ' . $publishResp->body());
        }
        return (string) ($publishResp->json('id') ?? $containerId);
    }

    /**
     * Telegram — sendMessage or sendPhoto to channel/chat.
     * channel_id is required (the chat_id of the channel/group, e.g. -1001234567890).
     */
    private function publishViaTelegram(SocialPostTarget $target, SocialPost $post, ?string $mediaUrl): string
    {
        if (! $target->channel_id) {
            throw new \RuntimeException('Telegram requires a chat_id (channel/group where the bot is admin).');
        }
        $token = $target->page->page_access_token;
        $base = "https://api.telegram.org/bot{$token}";

        if ($mediaUrl) {
            $resp = Http::post("{$base}/sendPhoto", [
                'chat_id' => $target->channel_id,
                'photo'   => $mediaUrl,
                'caption' => $post->content ?? '',
            ]);
        } else {
            $resp = Http::post("{$base}/sendMessage", [
                'chat_id' => $target->channel_id,
                'text'    => $post->content ?? '',
            ]);
        }

        if (! $resp->successful() || ! ($resp->json('ok') ?? false)) {
            throw new \RuntimeException('Telegram publish failed: ' . $resp->body());
        }
        return (string) ($resp->json('result.message_id') ?? '');
    }

    /**
     * Slack — chat.postMessage to a channel ID.
     * Bot must have been invited to the channel.
     */
    private function publishViaSlack(SocialPostTarget $target, SocialPost $post): string
    {
        if (! $target->channel_id) {
            throw new \RuntimeException('Slack requires a channel ID (e.g. C0123ABC).');
        }
        $token = $target->page->page_access_token;

        $resp = Http::withToken($token)
            ->post('https://slack.com/api/chat.postMessage', [
                'channel' => $target->channel_id,
                'text'    => $post->content ?? '',
            ])->json();

        if (! ($resp['ok'] ?? false)) {
            throw new \RuntimeException('Slack publish failed: ' . ($resp['error'] ?? 'unknown'));
        }
        return (string) ($resp['ts'] ?? '');
    }

    /**
     * Discord — POST /channels/{id}/messages.
     * channel_id is required; bot must have Send Messages permission in that channel.
     */
    private function publishViaDiscord(SocialPostTarget $target, SocialPost $post, ?string $mediaUrl): string
    {
        if (! $target->channel_id) {
            throw new \RuntimeException('Discord requires a channel ID.');
        }
        $token = $target->page->page_access_token;

        $payload = ['content' => $post->content ?? ''];
        // Discord supports embedding image URLs by including them in content;
        // proper file upload needs multipart, deferred to v2.
        if ($mediaUrl) {
            $payload['content'] = trim(($post->content ?? '') . "\n" . $mediaUrl);
        }

        $resp = Http::withHeaders(['Authorization' => 'Bot ' . $token])
            ->post("https://discord.com/api/v10/channels/{$target->channel_id}/messages", $payload);

        if (! $resp->successful()) {
            throw new \RuntimeException('Discord publish failed: ' . $resp->body());
        }
        return (string) ($resp->json('id') ?? '');
    }
}
