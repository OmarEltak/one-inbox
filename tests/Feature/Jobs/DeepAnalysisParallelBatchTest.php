<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Events\DeepAnalysisCompleted;
use App\Jobs\AnalyzeDeepAnalysisBatchJob;
use App\Jobs\DispatchDeepAnalysisJob;
use App\Jobs\FinalizeDeepAnalysisJob;
use App\Models\AiCreditLedgerEntry;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\DeepAnalysis;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Ai\DeepAnalysisService;
use App\Services\Billing\AiCredits;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

final class ParallelOkProvider implements AiProviderInterface
{
    public function generateResponse(\App\Models\Conversation $c, \App\Models\Message $m, \App\Models\AiConfig $cfg): string { return ''; }
    public function scoreMessage(\App\Models\Message $m, \App\Models\Contact $c): array { return []; }
    public function analyzeConversation(\App\Models\Conversation $c): array { return []; }
    public function processCommand(string $command, int $teamId): array { return []; }
    public function generateText(string $systemPrompt, string $userMessage): string
    {
        return json_encode([
            'themes' => [['name' => 'pricing', 'count' => 3]],
            'hot_leads' => [],
            'objections' => [],
            'summary' => 'chunk summary',
        ]);
    }
}

function makeParallelTeam(): Team
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'     => 'Parallel Co',
        'slug'     => 'team-' . Str::random(8),
        'owner_id' => $user->id,
    ]);
    AiCreditLedgerEntry::query()->where('team_id', $team->id)->delete();
    Cache::flush();
    app(AiCredits::class)->grant($team, 5000, AiCreditLedgerEntry::BALANCE_MONTHLY, 'test_seed');
    return $team->fresh();
}

function seedParallelCohort(Team $team, int $count): void
{
    $page = Page::factory()->create(['team_id' => $team->id]);
    for ($i = 0; $i < $count; $i++) {
        $contact = Contact::factory()->create(['team_id' => $team->id]);
        Conversation::create([
            'team_id'                   => $team->id,
            'contact_id'                => $contact->id,
            'page_id'                   => $page->id,
            'platform'                  => 'facebook',
            'platform_conversation_id'  => 'pc-' . Str::random(10),
            'status'                    => 'new',
            'last_message_at'           => now()->subMinutes($i + 1),
        ]);
    }
}

beforeEach(function () {
    config()->set('ai_costs.deep_analysis_per_100_contacts', 5);
    config()->set('ai_costs.deep_analysis_minimum', 10);
});

it('fans out one AnalyzeDeepAnalysisBatchJob per chunk inside an allow-failures batch', function () {
    Bus::fake();

    $team = makeParallelTeam();
    seedParallelCohort($team, 300); // 3 chunks of 100

    $this->app->bind(AiProviderInterface::class, fn () => new ParallelOkProvider);

    $analysis = app(DeepAnalysisService::class)->dispatch(
        $team,
        null,
        [],
        DeepAnalysis::MODE_CUSTOMER_THEMES,
        ['confirmation_token' => 'test-confirmed'],
    );

    (new DispatchDeepAnalysisJob($analysis->id))->handle(
        app(DeepAnalysisService::class),
        app(AiCredits::class),
    );

    Bus::assertBatched(function (PendingBatch $batch) use ($analysis) {
        // Name encodes the deep-analysis id so Horizon/logs can trace it.
        if ($batch->name !== "deep-analysis-{$analysis->id}") {
            return false;
        }

        // One child job per 100-contact chunk → 3 for a 300-contact cohort.
        if ($batch->jobs->count() !== 3) {
            return false;
        }

        // Every child is the per-chunk job class, on the isolated queue.
        foreach ($batch->jobs as $job) {
            if (! $job instanceof AnalyzeDeepAnalysisBatchJob) {
                return false;
            }
        }

        // allowFailures() invariant: one failed chunk shouldn't kill the run.
        if (! $batch->allowsFailures()) {
            return false;
        }

        return $batch->queue() === 'heavy-analysis';
    });
});

it('finalizer aggregates cached chunk summaries and marks the run completed', function () {
    Event::fake([DeepAnalysisCompleted::class]);

    $team = makeParallelTeam();
    seedParallelCohort($team, 150); // 2 chunks

    $this->app->bind(AiProviderInterface::class, fn () => new ParallelOkProvider);

    $analysis = app(DeepAnalysisService::class)->dispatch(
        $team,
        null,
        [],
        DeepAnalysis::MODE_CUSTOMER_THEMES,
        ['confirmation_token' => 'test-confirmed'],
    );

    // Seed the per-chunk cache as if the Analyze jobs had already run.
    Cache::put(AnalyzeDeepAnalysisBatchJob::cacheKey($analysis->id, 0), [
        'themes'     => [['name' => 'pricing', 'count' => 2]],
        'hot_leads'  => [],
        'objections' => [],
        'summary'    => 'batch 0 summary',
    ], 3600);

    Cache::put(AnalyzeDeepAnalysisBatchJob::cacheKey($analysis->id, 1), [
        'themes'     => [['name' => 'shipping', 'count' => 1]],
        'hot_leads'  => [],
        'objections' => [],
        'summary'    => 'batch 1 summary',
    ], 3600);

    (new FinalizeDeepAnalysisJob(
        $analysis->id,
        2,
        (int) $team->id,
        (int) $analysis->cohort_size,
    ))->handle(app(DeepAnalysisService::class), app(AiCredits::class));

    $analysis->refresh();
    expect($analysis->status)->toBe(DeepAnalysis::STATUS_COMPLETED)
        ->and($analysis->result_json)->toBeArray()
        ->and($analysis->completed_at)->not->toBeNull()
        ->and($analysis->token_usage['batch_count'] ?? null)->toBe(2);

    Event::assertDispatched(DeepAnalysisCompleted::class);

    // Cache is cleaned up after the finalizer completes.
    expect(Cache::get(AnalyzeDeepAnalysisBatchJob::cacheKey($analysis->id, 0)))->toBeNull()
        ->and(Cache::get(AnalyzeDeepAnalysisBatchJob::cacheKey($analysis->id, 1)))->toBeNull();
});

it('AnalyzeDeepAnalysisBatchJob writes its summary to the chunk cache key', function () {
    $team = makeParallelTeam();
    seedParallelCohort($team, 5);
    $this->app->bind(AiProviderInterface::class, fn () => new ParallelOkProvider);

    $ids = Conversation::where('team_id', $team->id)->pluck('id')->all();

    (new AnalyzeDeepAnalysisBatchJob(
        deepAnalysisId: 999,
        chunkIndex: 7,
        conversationIds: $ids,
        mode: DeepAnalysis::MODE_CUSTOMER_THEMES,
    ))->handle(app(DeepAnalysisService::class));

    $cached = Cache::get(AnalyzeDeepAnalysisBatchJob::cacheKey(999, 7));
    expect($cached)->toBeArray()
        ->and($cached['summary'] ?? null)->toBe('chunk summary');
});
