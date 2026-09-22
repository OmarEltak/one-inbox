<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Services\Onboarding\ProgressService;
use Illuminate\Support\Facades\Cache;

/**
 * Phase C — flushes ProgressService cache on relevant model changes.
 *
 * Wired in AppServiceProvider::boot(). Rather than scatter Cache::forget
 * calls across dispatch sites (against Team::canDispatchAi discipline, pin
 * #4 — same principle applies here for the cache key), we centralize the
 * invalidation on the model events themselves.
 */
final class ProgressCacheObserver
{
    public function __construct(private readonly ProgressService $progress) {}

    public function teamSaved(Team $team): void
    {
        $this->progress->forget($team);
    }

    public function pageSaved(Page $page): void
    {
        $this->forgetForTeamId($page->team_id);
    }

    public function pageDeleted(Page $page): void
    {
        $this->forgetForTeamId($page->team_id);
    }

    public function aiConfigSaved(AiConfig $config): void
    {
        $this->forgetForTeamId($config->team_id);
    }

    public function messageCreated(Message $message): void
    {
        // Messages carry only conversation_id — look up the team via
        // conversation. Wrapped so a stray unloadable relationship never
        // breaks the save.
        try {
            $conv = Conversation::find($message->conversation_id);
            if ($conv) {
                $this->forgetForTeamId($conv->team_id);
            }
        } catch (\Throwable) {
            // best-effort — cache will self-heal within TTL
        }
    }

    private function forgetForTeamId(?int $teamId): void
    {
        if ($teamId === null) {
            return;
        }
        Cache::forget("onboarding.progress.{$teamId}");
    }
}
