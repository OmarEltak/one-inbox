<?php

declare(strict_types=1);

use App\Models\AiCreditLedgerEntry;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Ai\DeepAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('ai_costs.deep_analysis_per_100_contacts', 5);
    config()->set('ai_costs.deep_analysis_minimum', 10);
});

function makeDeepAnalysisTeam(): Team
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'     => 'DA Co',
        'slug'     => 'team-' . Str::random(8),
        'owner_id' => $user->id,
    ]);
    AiCreditLedgerEntry::query()->where('team_id', $team->id)->delete();

    return $team->fresh();
}

function seedConversations(Team $team, int $count): void
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

it('applies the minimum floor for small cohorts', function () {
    $team = makeDeepAnalysisTeam();
    seedConversations($team, 50);

    $quote = app(DeepAnalysisService::class)->quote($team, []);

    // ceil(50/100) * 5 = 5, but minimum is 10 → floor wins.
    expect($quote['cost'])->toBe(10)
        ->and($quote['cohort_size'])->toBe(50);
});

it('scales linearly for 1000-contact cohorts', function () {
    $team = makeDeepAnalysisTeam();
    seedConversations($team, 1000);

    $quote = app(DeepAnalysisService::class)->quote($team, []);

    // ceil(1000/100) * 5 = 50
    expect($quote['cost'])->toBe(50)
        ->and($quote['cohort_size'])->toBe(1000);
});

it('caps the cohort at MAX_COHORT_SIZE to prevent runaway cost', function () {
    $team = makeDeepAnalysisTeam();
    seedConversations($team, 100);

    $quote = app(DeepAnalysisService::class)->quote($team, ['limit' => 999999]);

    expect($quote['cohort_size'])->toBeLessThanOrEqual(DeepAnalysisService::MAX_COHORT_SIZE);
});

it('rounds up partial hundreds using ceil, not floor', function () {
    $team = makeDeepAnalysisTeam();
    seedConversations($team, 350);

    $quote = app(DeepAnalysisService::class)->quote($team, []);

    // ceil(350/100) * 5 = 4 * 5 = 20
    expect($quote['cost'])->toBe(20);
});

it('returns zero cohort and the minimum floor cost when team has no conversations', function () {
    $team = makeDeepAnalysisTeam();

    $quote = app(DeepAnalysisService::class)->quote($team, []);

    expect($quote['cohort_size'])->toBe(0)
        ->and($quote['cost'])->toBe(10); // minimum floor still applies — intentional per spec
});
