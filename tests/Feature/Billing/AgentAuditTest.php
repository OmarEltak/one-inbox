<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Jobs\DispatchDeepAnalysisJob;
use App\Livewire\AiChat;
use App\Models\AiCreditLedgerEntry;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\DeepAnalysis;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Ai\AgentAuditService;
use App\Services\Ai\DeepAnalysisService;
use App\Services\Billing\AiCredits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Phase H test provider — minimal AiProviderInterface fake that returns a
 * fixed prose summary for generateText(). Mirrors FakeOkProvider from
 * tests/Feature/Jobs/DispatchDeepAnalysisJobTest but defined locally so this
 * file is self-contained.
 */
final class FakeAuditOkProvider implements AiProviderInterface
{
    public function generateResponse(\App\Models\Conversation $conversation, \App\Models\Message $incomingMessage, \App\Models\AiConfig $config): string
    {
        return '';
    }
    public function scoreMessage(\App\Models\Message $message, \App\Models\Contact $contact): array
    {
        return [];
    }
    public function analyzeConversation(\App\Models\Conversation $conversation): array
    {
        return [];
    }
    public function processCommand(string $command, int $teamId): array
    {
        return [];
    }
    public function generateText(string $systemPrompt, string $userMessage): string
    {
        return 'Prose evaluation of the two agents. Nothing stands out.';
    }
}

function makeAuditTeam(int $monthly = 1000): Team
{
    $owner = User::factory()->create();
    $team = Team::create([
        'name'     => 'Audit Co',
        'slug'     => 'team-' . Str::random(8),
        'owner_id' => $owner->id,
    ]);
    AiCreditLedgerEntry::query()->where('team_id', $team->id)->delete();
    Cache::flush();

    if ($monthly > 0) {
        app(AiCredits::class)->grant($team, $monthly, AiCreditLedgerEntry::BALANCE_MONTHLY, 'test_seed');
    }

    return $team->fresh();
}

/**
 * Seed a conversation with (inbound, outbound-user) pairs. Each pair's
 * $responseSeconds controls the delta between the inbound and the agent reply.
 *
 * @param  array<int, array{user_id: int, response_seconds: int}>  $pairs
 */
function seedConversationWithPairs(Team $team, Page $page, array $pairs, bool $completed = false): Conversation
{
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    $conv = Conversation::create([
        'team_id'                  => $team->id,
        'contact_id'               => $contact->id,
        'page_id'                  => $page->id,
        'platform'                 => 'facebook',
        'platform_conversation_id' => 'pc-' . Str::random(10),
        'status'                   => 'open',
        'sales_stage'              => $completed ? Conversation::STAGE_COMPLETED : Conversation::STAGE_ACTIVE,
        'last_message_at'          => now(),
    ]);

    $t = now()->subMinutes(count($pairs) * 60);
    foreach ($pairs as $pair) {
        $inboundAt = $t->copy();
        $outboundAt = $t->copy()->addSeconds($pair['response_seconds']);

        $in = Message::create([
            'conversation_id' => $conv->id,
            'direction'       => 'inbound',
            'sender_type'     => 'contact',
            'sender_id'       => $contact->id,
            'content_type'    => 'text',
            'content'         => 'hi',
        ]);
        $in->forceFill(['created_at' => $inboundAt, 'updated_at' => $inboundAt])->saveQuietly();

        $out = Message::create([
            'conversation_id'    => $conv->id,
            'direction'          => 'outbound',
            'sender_type'        => 'user',
            'sender_id'          => $pair['user_id'],
            'handled_by_user_id' => $pair['user_id'],
            'content_type'       => 'text',
            'content'            => 'hello from agent — this is a short enough reply to be an example snippet',
        ]);
        $out->forceFill(['created_at' => $outboundAt, 'updated_at' => $outboundAt])->saveQuietly();

        $t->addMinutes(30);
    }

    return $conv;
}

it('aggregates per-agent stats from message history', function () {
    $team = makeAuditTeam();
    $page = Page::factory()->create(['team_id' => $team->id]);
    $alice = User::factory()->create(['name' => 'Alice']);
    $bob = User::factory()->create(['name' => 'Bob']);

    // Alice handles two conversations; one converts.
    seedConversationWithPairs($team, $page, [
        ['user_id' => $alice->id, 'response_seconds' => 60],
        ['user_id' => $alice->id, 'response_seconds' => 120],
    ], completed: true);
    seedConversationWithPairs($team, $page, [
        ['user_id' => $alice->id, 'response_seconds' => 240],
    ]);

    // Bob handles one conversation.
    seedConversationWithPairs($team, $page, [
        ['user_id' => $bob->id, 'response_seconds' => 30],
    ]);

    $stats = app(AgentAuditService::class)->perAgentStats($team, []);

    expect($stats)->toHaveCount(2);

    $alice_stats = collect($stats)->firstWhere('user_id', $alice->id);
    $bob_stats = collect($stats)->firstWhere('user_id', $bob->id);

    expect($alice_stats)->not->toBeNull();
    expect($alice_stats['messages_sent'])->toBe(3);
    expect($alice_stats['conversations_touched'])->toBe(2);
    // avg of 60,120,240 = 140
    expect($alice_stats['avg_response_time_seconds'])->toBe(140.0);
    // 1 of 2 conversations completed = 0.5
    expect($alice_stats['conversion_rate'])->toBe(0.5);
    expect($alice_stats['examples'])->toHaveCount(2);
    expect($alice_stats['examples'][0]['snippet'])->toBeString()
        ->and(mb_strlen($alice_stats['examples'][0]['snippet']))->toBeLessThanOrEqual(AgentAuditService::EXAMPLE_SNIPPET_CHARS + 3);

    expect($bob_stats)->not->toBeNull();
    expect($bob_stats['messages_sent'])->toBe(1);
    expect($bob_stats['conversations_touched'])->toBe(1);
    expect($bob_stats['avg_response_time_seconds'])->toBe(30.0);
    // 0 of 1 completed
    expect($bob_stats['conversion_rate'])->toBe(0.0);
});

it('reports null handled_by_user_id rows as Unattributed', function () {
    $team = makeAuditTeam();
    $page = Page::factory()->create(['team_id' => $team->id]);
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    $conv = Conversation::create([
        'team_id'                  => $team->id,
        'contact_id'               => $contact->id,
        'page_id'                  => $page->id,
        'platform'                 => 'facebook',
        'platform_conversation_id' => 'pc-' . Str::random(10),
        'status'                   => 'open',
        'sales_stage'              => Conversation::STAGE_ACTIVE,
        'last_message_at'          => now(),
    ]);

    Message::create([
        'conversation_id' => $conv->id,
        'direction'       => 'outbound',
        'sender_type'     => 'user',
        'sender_id'       => null,
        'handled_by_user_id' => null,
        'content_type'    => 'text',
        'content'         => 'legacy reply with no attribution',
    ]);

    $stats = app(AgentAuditService::class)->perAgentStats($team, []);

    expect($stats)->toHaveCount(1);
    expect($stats[0]['user_id'])->toBeNull();
    expect($stats[0]['user_name'])->toBe('Unattributed');
});

it('deep analysis dispatch with agent_audit mode writes a row with mode = agent_audit', function () {
    $team = makeAuditTeam(monthly: 1000);
    $page = Page::factory()->create(['team_id' => $team->id]);
    $alice = User::factory()->create();
    seedConversationWithPairs($team, $page, [
        ['user_id' => $alice->id, 'response_seconds' => 60],
    ]);

    $this->app->bind(AiProviderInterface::class, fn () => new FakeAuditOkProvider);

    $analysis = app(DeepAnalysisService::class)->dispatch(
        $team,
        null,
        [],
        DeepAnalysis::MODE_AGENT_AUDIT,
        ['confirmation_token' => 'test-confirmed'],
    );

    expect($analysis->mode)->toBe(DeepAnalysis::MODE_AGENT_AUDIT);
    expect($analysis->status)->toBe(DeepAnalysis::STATUS_QUEUED);

    (new DispatchDeepAnalysisJob($analysis->id))->handle(
        app(DeepAnalysisService::class),
        app(AiCredits::class),
    );

    $analysis->refresh();
    expect($analysis->status)->toBe(DeepAnalysis::STATUS_COMPLETED);
    expect($analysis->result_json)->toBeArray();
    expect($analysis->result_json['stats'] ?? null)->toBeArray();
    expect($analysis->result_json['summary'] ?? null)->toBeString();
});

it('AiChat parser detects "audit how our moderator handled Mishkah" and routes to MODE_AGENT_AUDIT', function () {
    $owner = User::factory()->create();
    $team = Team::create([
        'name'     => 'Mishkah',
        'slug'     => 'team-' . Str::random(8),
        'owner_id' => $owner->id,
    ]);
    Page::factory()->create(['team_id' => $team->id, 'name' => 'Mishkah']);
    $owner->current_team_id = $team->id;
    $owner->save();

    $component = new AiChat;
    $reflection = new ReflectionClass($component);
    $method = $reflection->getMethod('parseDeepAnalysisRequest');
    $method->setAccessible(true);

    $out = $method->invoke($component, $team->fresh(), 'audit how our moderator handled Mishkah');

    expect($out)->not->toBeNull();
    expect($out['mode'])->toBe(DeepAnalysis::MODE_AGENT_AUDIT);
});

it('AiChat parser detects "evaluate our agents" variant', function () {
    $owner = User::factory()->create();
    $team = Team::create([
        'name'     => 'Mishkah',
        'slug'     => 'team-' . Str::random(8),
        'owner_id' => $owner->id,
    ]);

    $component = new AiChat;
    $reflection = new ReflectionClass($component);
    $method = $reflection->getMethod('parseDeepAnalysisRequest');
    $method->setAccessible(true);

    $out = $method->invoke($component, $team, 'evaluate our agents');
    expect($out)->not->toBeNull()
        ->and($out['mode'])->toBe(DeepAnalysis::MODE_AGENT_AUDIT);

    $out2 = $method->invoke($component, $team, 'how did our team handle customer requests');
    expect($out2)->not->toBeNull()
        ->and($out2['mode'])->toBe(DeepAnalysis::MODE_AGENT_AUDIT);
});

it('cost formula floors at agent_audit for very small cohorts', function () {
    config()->set('ai_costs.deep_analysis_per_100_contacts', 5);
    config()->set('ai_costs.deep_analysis_minimum', 10);
    config()->set('ai_costs.agent_audit', 3);

    $team = makeAuditTeam();
    $page = Page::factory()->create(['team_id' => $team->id]);
    $alice = User::factory()->create();

    // 1 conversation only — ceil(1/100)*5 = 5, but agent_audit floor is 3,
    // so max(3, 5) = 5. Also verify a 0-conversation audit returns the floor.
    seedConversationWithPairs($team, $page, [
        ['user_id' => $alice->id, 'response_seconds' => 60],
    ]);

    $quote = app(DeepAnalysisService::class)->quote($team, [], DeepAnalysis::MODE_AGENT_AUDIT);
    // scaled = ceil(1/100)*5 = 5, floor = 3 → max(3,5) = 5.
    expect($quote['cost'])->toBe(5);

    // zero-cohort scenario: scaled = 0, floor = 3 → cost = 3.
    $emptyTeam = makeAuditTeam();
    $emptyQuote = app(DeepAnalysisService::class)->quote($emptyTeam, [], DeepAnalysis::MODE_AGENT_AUDIT);
    expect($emptyQuote['cohort_size'])->toBe(0);
    expect($emptyQuote['cost'])->toBe(3);
});

it('cost formula scales to 25 for a 500-conversation agent audit', function () {
    config()->set('ai_costs.deep_analysis_per_100_contacts', 5);
    config()->set('ai_costs.deep_analysis_minimum', 10);
    config()->set('ai_costs.agent_audit', 3);

    $team = makeAuditTeam();
    $page = Page::factory()->create(['team_id' => $team->id]);
    for ($i = 0; $i < 500; $i++) {
        $contact = Contact::factory()->create(['team_id' => $team->id]);
        Conversation::create([
            'team_id'                  => $team->id,
            'contact_id'               => $contact->id,
            'page_id'                  => $page->id,
            'platform'                 => 'facebook',
            'platform_conversation_id' => 'pc-' . Str::random(10),
            'status'                   => 'open',
            'sales_stage'              => Conversation::STAGE_ACTIVE,
            'last_message_at'          => now()->subMinutes($i + 1),
        ]);
    }

    $quote = app(DeepAnalysisService::class)->quote($team, [], DeepAnalysis::MODE_AGENT_AUDIT);

    // ceil(500/100) * 5 = 25, max(3, 25) = 25.
    expect($quote['cost'])->toBe(25);
    expect($quote['cohort_size'])->toBe(500);
});

it('drops response-time pairs older than 24 hours (noise filter)', function () {
    $team = makeAuditTeam();
    $page = Page::factory()->create(['team_id' => $team->id]);
    $alice = User::factory()->create();
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    $conv = Conversation::create([
        'team_id'                  => $team->id,
        'contact_id'               => $contact->id,
        'page_id'                  => $page->id,
        'platform'                 => 'facebook',
        'platform_conversation_id' => 'pc-' . Str::random(10),
        'status'                   => 'open',
        'sales_stage'              => Conversation::STAGE_ACTIVE,
        'last_message_at'          => now(),
    ]);

    $inboundAt = now()->subDays(5);
    $in = Message::create([
        'conversation_id' => $conv->id,
        'direction'       => 'inbound',
        'sender_type'     => 'contact',
        'sender_id'       => $contact->id,
        'content_type'    => 'text',
        'content'         => 'hello',
    ]);
    $in->forceFill(['created_at' => $inboundAt, 'updated_at' => $inboundAt])->saveQuietly();

    // Reply 5 days later — should NOT count as a response-time sample. The
    // outbound message's created_at is `now()`, which is > 24h after
    // $inboundAt, so pairResponseDelta must return null.
    Message::create([
        'conversation_id'    => $conv->id,
        'direction'          => 'outbound',
        'sender_type'        => 'user',
        'sender_id'          => $alice->id,
        'handled_by_user_id' => $alice->id,
        'content_type'       => 'text',
        'content'            => 'late reply',
    ]);

    $stats = app(AgentAuditService::class)->perAgentStats($team, []);
    expect($stats)->toHaveCount(1);
    // No valid response pair → avg stays null, not 432000.
    expect($stats[0]['avg_response_time_seconds'])->toBeNull();
});
