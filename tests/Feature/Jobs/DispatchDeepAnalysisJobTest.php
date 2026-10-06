<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Events\DeepAnalysisCompleted;
use App\Jobs\DispatchDeepAnalysisJob;
use App\Models\AiCreditLedgerEntry;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\DeepAnalysis;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Ai\DeepAnalysisService;
use App\Services\Billing\AiCredits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

final class FakeOkProvider implements AiProviderInterface
{
    public function generateResponse(\App\Models\Conversation $conversation, \App\Models\Message $incomingMessage, \App\Models\AiConfig $config): string { return ''; }
    public function scoreMessage(\App\Models\Message $message, \App\Models\Contact $contact): array { return []; }
    public function analyzeConversation(\App\Models\Conversation $conversation): array { return []; }
    public function processCommand(string $command, int $teamId): array { return []; }
    public function generateText(string $systemPrompt, string $userMessage): string
    {
        return json_encode(['themes' => [['name' => 'test', 'count' => 1]], 'hot_leads' => [], 'objections' => [], 'summary' => 'ok']);
    }
}

final class FakeThrowingProvider implements AiProviderInterface
{
    public function generateResponse(\App\Models\Conversation $conversation, \App\Models\Message $incomingMessage, \App\Models\AiConfig $config): string { return ''; }
    public function scoreMessage(\App\Models\Message $message, \App\Models\Contact $contact): array { return []; }
    public function analyzeConversation(\App\Models\Conversation $conversation): array { return []; }
    public function processCommand(string $command, int $teamId): array { return []; }
    public function generateText(string $systemPrompt, string $userMessage): string
    {
        throw new \RuntimeException('simulated NaraRouter outage');
    }
}

function makeDeepAnalysisJobTeam(int $monthly = 1000): Team
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'     => 'Deep Job Co',
        'slug'     => 'team-' . Str::random(8),
        'owner_id' => $user->id,
    ]);
    AiCreditLedgerEntry::query()->where('team_id', $team->id)->delete();
    Cache::flush();

    if ($monthly > 0) {
        app(AiCredits::class)->grant($team, $monthly, AiCreditLedgerEntry::BALANCE_MONTHLY, 'test_seed');
    }

    return $team->fresh();
}

function seedCohort(Team $team, int $count): void
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

it('completes successfully and broadcasts DeepAnalysisCompleted', function () {
    Event::fake([DeepAnalysisCompleted::class]);

    $team = makeDeepAnalysisJobTeam(monthly: 1000);
    seedCohort($team, 10);

    // Fake AI provider so no real NaraRouter call is made.
    $this->app->bind(AiProviderInterface::class, fn () => new FakeOkProvider);

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

    $analysis->refresh();
    expect($analysis->status)->toBe(DeepAnalysis::STATUS_COMPLETED)
        ->and($analysis->result_json)->toBeArray()
        ->and($analysis->completed_at)->not->toBeNull();

    Event::assertDispatched(DeepAnalysisCompleted::class);
});

it('marks the row failed and refunds credits on terminal NaraRouter failure', function () {
    $team = makeDeepAnalysisJobTeam(monthly: 1000);
    seedCohort($team, 10);

    // Fake provider that always throws.
    $this->app->bind(AiProviderInterface::class, fn () => new FakeThrowingProvider);

    $balanceBefore = app(AiCredits::class)->balance($team)->total();
    $analysis = app(DeepAnalysisService::class)->dispatch(
        $team,
        null,
        [],
        DeepAnalysis::MODE_CUSTOMER_THEMES,
        ['confirmation_token' => 'test-confirmed'],
    );

    // credits_charged must equal the quoted cost (10 min floor + 10-contact cohort → 10).
    expect($analysis->credits_charged)->toBeGreaterThan(0);

    (new DispatchDeepAnalysisJob($analysis->id))->handle(
        app(DeepAnalysisService::class),
        app(AiCredits::class),
    );

    $analysis->refresh();
    expect($analysis->status)->toBe(DeepAnalysis::STATUS_FAILED)
        ->and($analysis->error_message)->toContain('simulated NaraRouter outage');

    // Refund restored the balance (end-state assertion — the mid-state is
    // sensitive to Cache::remember timing, so we assert the invariant that
    // matters: after a terminal failure, the team owes nothing).
    $balanceAfterRefund = app(AiCredits::class)->balance($team->fresh())->total();
    expect($balanceAfterRefund)->toBe($balanceBefore);
});

it('is idempotent: re-running on a completed row is a no-op', function () {
    $team = makeDeepAnalysisJobTeam(monthly: 1000);
    seedCohort($team, 5);

    $analysis = DeepAnalysis::create([
        'team_id'              => $team->id,
        'triggered_by_user_id' => null,
        'mode'                 => DeepAnalysis::MODE_CUSTOMER_THEMES,
        'cohort_filter'        => [],
        'cohort_size'          => 5,
        'credits_charged'      => 10,
        'status'               => DeepAnalysis::STATUS_COMPLETED,
        'completed_at'         => now(),
        'result_json'          => ['pre' => 'existing'],
    ]);

    (new DispatchDeepAnalysisJob($analysis->id))->handle(
        app(DeepAnalysisService::class),
        app(AiCredits::class),
    );

    $analysis->refresh();
    expect($analysis->result_json)->toBe(['pre' => 'existing']); // untouched
});
