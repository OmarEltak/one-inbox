<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\AiProviderInterface;
use App\Events\AiChatTurnCompleted;
use App\Models\AiCommand;
use App\Models\Team;
use App\Services\Ai\AdminChatContext;
use App\Services\Billing\AiCredits;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Process a single AiChat turn asynchronously.
 *
 * Driver: a 2026-10-08 user complaint that pressing Send and then navigating
 * away ("I thought it would send in the background") caused the request to
 * silently drop — Livewire's sendMessage() blocked for 5-15s on the NaraRouter
 * call, and a navigate-away cancelled the whole thing before AiCommand was
 * ever persisted. Now AiChat::sendMessage persists an AiCommand with
 * status='pending' first, then dispatches this job, so the question survives
 * any navigation. This job calls NaraRouter, writes the response back, and
 * broadcasts AiChatTurnCompleted — the Livewire component picks it up (or on
 * next mount, loads the completed row from the DB).
 */
class ProcessAiChatTurn implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;
    public int $timeout = 90;

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     *     Snapshot of the preceding chat turns from the browser, max 12 messages,
     *     each clipped to 2000 chars. We send this instead of re-loading from the
     *     DB so the exact context the user saw is what the model receives.
     */
    public function __construct(
        public int $commandId,
        public array $history,
    ) {
    }

    public function handle(AiProviderInterface $provider, AdminChatContext $chatContext, AiCredits $credits): void
    {
        $command = AiCommand::find($this->commandId);
        if (! $command) {
            Log::warning('ProcessAiChatTurn: command vanished', ['id' => $this->commandId]);
            return;
        }

        $team = Team::find($command->team_id);
        if (! $team) {
            $command->update(['status' => 'failed', 'response' => 'Team no longer exists.']);
            return;
        }

        $text = (string) $command->command;

        // Context block — same pieces sendMessage() used to build inline.
        $recentUserTalk = collect($this->history)
            ->where('role', 'user')
            ->pluck('content')
            ->implode("\n");

        $pageIds = $chatContext->mentionedPageIds($team->id, $text, $recentUserTalk);
        if ($pageIds === []) {
            $digest = $chatContext->customerDigest($team->id);
        } elseif (count($pageIds) === 1) {
            $digest = $chatContext->customerDigest($team->id, pageId: $pageIds[0], expanded: true);
        } else {
            $digest = collect($pageIds)->map(fn ($id) => $chatContext->customerDigest(
                $team->id, charBudget: intdiv(6000, count($pageIds)), pageId: $id,
            ))->implode("\n\n");
        }

        $recentTalk = collect($this->history)->pluck('content')->implode("\n");

        $analyticsContext = $this->buildAnalyticsContext($team->id)
            . "\n\n" . $chatContext->mentionedContacts($team->id, $text, $recentTalk)
            . "\n\n" . $digest;

        $history = collect($this->history)
            ->filter(fn ($m) => $m['role'] === 'user' || $m['role'] === 'assistant')
            ->map(fn ($m) => [
                'role' => $m['role'] === 'user' ? 'user' : 'model',
                'content' => Str::limit((string) $m['content'], 2000),
            ])
            ->values()
            ->all();

        $aiSucceeded = false;
        try {
            $response = $this->callWithRetry($provider, $team->id, (int) $command->user_id, $text, $analyticsContext, $history);
            $aiSucceeded = true;
        } catch (\Throwable $e) {
            Log::error('ProcessAiChatTurn: NaraRouter call failed', [
                'command_id' => $command->id,
                'error' => $e->getMessage(),
            ]);
            $response = __('Sorry, I encountered an error processing your request. Please try again.');
        }

        $response = \App\Livewire\AiChat::stripInternalIdsPublic($response);
        if (trim((string) $response) === '') {
            $response = __('I could not put an answer together for that. Try rephrasing, or ask about a specific contact or campaign.');
        }

        $command->update([
            'status'   => $aiSucceeded ? 'completed' : 'failed',
            'response' => $response,
        ]);

        if ($aiSucceeded) {
            try {
                $credits->charge(
                    team: $team,
                    action: 'ai_chat_turn',
                    meta: [
                        'idempotency_key'  => 'aichat:' . hash('sha256', $team->id . '|' . $command->user_id . '|' . trim($text)),
                        'cost_source_type' => AiCommand::class,
                        'cost_source_id'   => $command->id,
                    ],
                );
            } catch (\Throwable $e) {
                Log::warning("AiCredits::charge failed for team {$team->id}", ['error' => $e->getMessage()]);
            }
        }

        AiChatTurnCompleted::dispatch($team->id, (int) $command->user_id, $command->id, $response);
    }

    /**
     * Mirror of AiChat::callAiWithRetry — kept in sync by construction (same
     * idempotency key shape and backoff). If you edit one, grep for the other.
     */
    protected function callWithRetry(AiProviderInterface $provider, int $teamId, int $userId, string $message, string $analyticsContext, array $history): string
    {
        $idempotencyKey = 'aichat:idem:' . hash('sha256', $teamId . '|' . $userId . '|' . trim($message));
        $cached = Cache::get($idempotencyKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $backoffMs = [0, 500, 2000];
        $lastThrowable = null;

        foreach ($backoffMs as $delay) {
            if ($delay > 0) {
                usleep($delay * 1000);
            }

            try {
                $response = $provider->chatWithAdmin($message, $teamId, $analyticsContext, $history);
                if (is_string($response) && trim($response) !== '') {
                    Cache::put($idempotencyKey, $response, 60);
                    return $response;
                }
            } catch (\Throwable $e) {
                $lastThrowable = $e;
            }
        }

        if ($lastThrowable !== null) {
            throw $lastThrowable;
        }
        throw new \RuntimeException('AI provider returned an empty response after 3 attempts.');
    }

    /**
     * Minimal analytics block — ported from AiChat::buildAnalyticsContext but
     * trimmed to the parts the admin chat actually needs. Kept here (not
     * extracted to a shared service) because the sync and async paths diverge
     * subtly and we want each to be greppable in one place.
     */
    protected function buildAnalyticsContext(int $teamId): string
    {
        $team = Team::find($teamId);
        if (! $team) {
            return '';
        }

        $pages = $team->pages()->where('is_active', true)->get(['id', 'name', 'platform']);
        $pageLines = $pages->map(fn ($p) => "- {$p->name} ({$p->platform}, id={$p->id})")->implode("\n");

        return "Active pages:\n{$pageLines}";
    }
}
