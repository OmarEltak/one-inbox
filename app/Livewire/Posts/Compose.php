<?php

declare(strict_types=1);

namespace App\Livewire\Posts;

use App\Jobs\PublishPostJob;
use App\Models\Page;
use App\Models\SocialPost;
use App\Models\SocialPostTarget;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Compose-once-publish-everywhere page.
 *
 * UX:
 *   - Single textarea + optional image upload.
 *   - Multi-platform checkboxes auto-disabled when the team isn't connected.
 *   - For Telegram/Slack/Discord the user provides a channel/chat id; we
 *     don't auto-fetch lists in v1 because each platform has different
 *     pagination and permission edge cases.
 *
 * Limits we enforce on submit:
 *   - At least one platform selected.
 *   - Instagram requires media (Graph API rejects text-only posts).
 *   - TG/Slack/Discord targets require a channel id when selected.
 */
class Compose extends Component
{
    use WithFileUploads;

    /** @var array<string,bool> */
    public array $selectedPlatforms = [
        'facebook'  => false,
        'instagram' => false,
        'telegram'  => false,
        'slack'     => false,
        'discord'   => false,
    ];

    /** Channel ids for platforms that need them (per-platform free-form input). */
    public string $telegramChatId  = '';
    public string $slackChannelId  = '';
    public string $discordChannelId = '';

    public string $content = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $image = null;

    public ?int $lastPostId = null;
    public string $statusMessage = '';

    protected function rules(): array
    {
        return [
            'content'         => 'nullable|string|max:5000',
            'image'           => 'nullable|image|max:10240',
            'telegramChatId'  => 'nullable|string|max:64',
            'slackChannelId'  => 'nullable|string|max:64',
            'discordChannelId' => 'nullable|string|max:64',
        ];
    }

    #[Computed]
    public function pages()
    {
        $team = Auth::user()->currentTeam;
        if (! $team) {
            return collect();
        }
        return Page::where('team_id', $team->id)
            ->where('is_active', true)
            ->whereIn('platform', ['facebook', 'instagram', 'telegram', 'slack', 'discord'])
            ->get()
            ->groupBy('platform');
    }

    #[Computed]
    public function recentPosts()
    {
        $team = Auth::user()->currentTeam;
        if (! $team) {
            return collect();
        }
        return SocialPost::with('targets.page')
            ->where('team_id', $team->id)
            ->whereNotIn('status', [SocialPost::STATUS_DRAFT])
            ->latest('id')
            ->limit(10)
            ->get();
    }

    public function publish(): void
    {
        $this->validate();

        $team = Auth::user()->currentTeam;
        if (! $team) {
            $this->addError('content', 'No active team.');
            return;
        }

        $picked = array_keys(array_filter($this->selectedPlatforms));
        if (empty($picked)) {
            $this->addError('selectedPlatforms', 'Pick at least one platform.');
            return;
        }
        if (trim($this->content) === '' && ! $this->image) {
            $this->addError('content', 'Add some text or an image.');
            return;
        }

        $pagesByPlatform = $this->pages;

        // Validate channel ids where required.
        $channelMap = [
            'telegram' => trim($this->telegramChatId),
            'slack'    => trim($this->slackChannelId),
            'discord'  => trim($this->discordChannelId),
        ];
        foreach ($picked as $p) {
            if (in_array($p, ['telegram', 'slack', 'discord'], true) && $channelMap[$p] === '') {
                $this->addError('selectedPlatforms', ucfirst($p) . ' requires a channel/chat id.');
                return;
            }
            if (! $pagesByPlatform->has($p)) {
                $this->addError('selectedPlatforms', 'You haven\'t connected ' . ucfirst($p) . ' yet.');
                return;
            }
        }

        // Persist media if uploaded.
        $mediaPath = null;
        $mediaType = null;
        if ($this->image) {
            $mediaPath = $this->image->store("social-posts/{$team->id}", 'public');
            $mediaType = 'image';
        }

        if (in_array('instagram', $picked, true) && ! $mediaPath) {
            $this->addError('image', 'Instagram requires an image. Upload one or unselect Instagram.');
            return;
        }

        $post = SocialPost::create([
            'team_id'      => $team->id,
            'user_id'      => Auth::id(),
            'content'      => $this->content,
            'media_path'   => $mediaPath,
            'media_type'   => $mediaType,
            'status'       => SocialPost::STATUS_QUEUED,
            'published_at' => null,
        ]);

        // One target row per (platform, page). FB/IG fan out to every connected page;
        // TG/Slack/Discord get one target each (the user-provided channel).
        foreach ($picked as $platform) {
            $pages = $pagesByPlatform->get($platform, collect());
            foreach ($pages as $page) {
                SocialPostTarget::create([
                    'social_post_id' => $post->id,
                    'page_id'        => $page->id,
                    'platform'       => $platform,
                    'channel_id'     => $channelMap[$platform] ?? null,
                    'status'         => SocialPostTarget::STATUS_PENDING,
                ]);
                // For TG/Slack/Discord one Page is enough — one target per platform.
                if (in_array($platform, ['telegram', 'slack', 'discord'], true)) {
                    break;
                }
            }
        }

        PublishPostJob::dispatch($post->id);

        $this->reset(['content', 'image', 'selectedPlatforms', 'telegramChatId', 'slackChannelId', 'discordChannelId']);
        $this->selectedPlatforms = [
            'facebook' => false, 'instagram' => false, 'telegram' => false, 'slack' => false, 'discord' => false,
        ];
        $this->lastPostId = $post->id;
        $this->statusMessage = 'Queued — fanning out to ' . count($picked) . ' platform(s). Refresh to see status.';
        unset($this->recentPosts);
    }

    public function render()
    {
        return view('livewire.posts.compose')->layout('layouts.app');
    }
}
