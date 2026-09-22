<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Livewire\Onboarding\MeetYourAi;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

// Phase A end-to-end: signed-in user walks all 4 steps of "Meet Your AI".
// Asserts AiConfig-equivalent seed persisted on the team AND that the AI
// produces at least one reply in the fake customer chat.

function makeMeetAiUser(): array
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'     => 'Acme Bakery',
        'slug'     => 'team-'.Str::random(8),
        'owner_id' => $user->id,
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return [$user->fresh(), $team->fresh()];
}

function bindAiMock(): Mockery\MockInterface
{
    $mock = Mockery::mock(AiProviderInterface::class);
    app()->instance(AiProviderInterface::class, $mock);
    return $mock;
}

it('registers the onboarding route with the auth middleware', function () {
    $this->get(route('onboarding.meet-your-ai'))
        ->assertRedirect(route('login'));
});

it('walks a new user through all 4 steps and persists the seed prompt', function () {
    [$user, $team] = makeMeetAiUser();
    $ai = bindAiMock();

    // Polish call in step 3.
    $ai->shouldReceive('generateText')
        ->once()
        ->andReturn("I'm the AI for Acme Bakery. We ship sourdough overnight.");
    // First AI turn in step 4 (auto-triggered after step 3).
    $ai->shouldReceive('generateText')
        ->once()
        ->andReturn("Yes — we deliver to most of Cairo within 24 hours.");

    $this->actingAs($user);

    Livewire::test(MeetYourAi::class)
        // Step 1
        ->assertSet('step', 1)
        ->call('pickBusinessType', 'restaurant')
        ->assertSet('step', 2)
        ->assertSet('businessType', 'restaurant')
        // Step 2 — Q1
        ->set('q1Offer', 'Fresh artisan sourdough shipped daily')
        ->call('submitQuestion')
        ->assertSet('questionIndex', 2)
        // Step 2 — Q2
        ->set('q2TopQuestion', 'Do you deliver to my area?')
        ->call('submitQuestion')
        ->assertSet('questionIndex', 3)
        // Step 2 — Q3 → triggers step 3 generation
        ->set('q3Tone', 'friendly')
        ->call('submitQuestion')
        ->assertSet('step', 3)
        // Trigger the generation the way the front-end would (browser event).
        ->call('generate')
        ->assertSet('step', 4)
        // Step 4 — the customer's first message is auto-seeded from Q2.
        ->assertCount('chatMessages', 1)
        // Kick the AI turn as the front-end would.
        ->call('generateAiTurn')
        ->assertCount('chatMessages', 2);

    // Team state — seed saved, business type recorded, onboarding not yet completed.
    $team->refresh();
    expect($team->business_type)->toBe('restaurant');
    expect($team->onboarding_completed_at)->toBeNull();
    expect($team->settings['onboarding_ai_seed']['system_prompt'])
        ->toContain('Acme Bakery');
    expect($team->settings['onboarding_ai_seed']['polished'])->toBeTrue();
});

it('marks onboarding complete only when the user picks a CTA', function () {
    [$user, $team] = makeMeetAiUser();

    $this->actingAs($user);

    // Phase D reroutes teams without a trial/plan to pick-your-plan first.
    // A fresh factory-made team hits that branch, so assert the D-integrated target.
    Livewire::test(MeetYourAi::class)
        ->call('completeAndConnect')
        ->assertRedirect(route('onboarding.pick-your-plan'));

    expect($team->fresh()->onboarding_completed_at)->not->toBeNull();
});

it('keeps onboarding_completed_at NULL when user skips', function () {
    [$user, $team] = makeMeetAiUser();

    $this->actingAs($user);

    Livewire::test(MeetYourAi::class)
        ->call('skip')
        ->assertRedirect(route('dashboard'));

    $team->refresh();
    expect($team->onboarding_completed_at)->toBeNull();
    // But we DO record the skip so nudge emails can distinguish "never touched"
    // from "actively skipped".
    expect($team->settings['onboarding_skipped_at'] ?? null)->not->toBeNull();
});

it('falls back to raw template when NaraRouter is unavailable during step 3', function () {
    [$user, $team] = makeMeetAiUser();
    $ai = bindAiMock();

    // Polish call throws — composer must swallow it and save the raw template.
    $ai->shouldReceive('generateText')
        ->once()
        ->andThrow(new \App\Exceptions\AiAllProvidersUnavailable('cooldown'));

    $this->actingAs($user);

    Livewire::test(MeetYourAi::class)
        ->call('pickBusinessType', 'ecommerce')
        ->set('q1Offer', 'Handmade leather bags')
        ->call('submitQuestion')
        ->set('q2TopQuestion', 'Do you ship to my area?')
        ->call('submitQuestion')
        ->set('q3Tone', 'friendly')
        ->call('submitQuestion')
        ->call('generate')
        ->assertSet('step', 4)
        ->assertSet('polished', false);

    $team->refresh();
    $prompt = (string) data_get($team->settings, 'onboarding_ai_seed.system_prompt');
    expect($prompt)->toContain('You are the AI sales assistant for Acme Bakery');
    // Anti-regression per CLAUDE.md pin #5.
    expect($prompt)
        ->not->toContain('apologize')
        ->not->toContain('having a moment');
});

it('shows a soft error banner (not a fake AI reply) when NaraRouter fails mid-chat', function () {
    [$user, $team] = makeMeetAiUser();
    $ai = bindAiMock();

    // Step 3 polish succeeds.
    $ai->shouldReceive('generateText')
        ->once()
        ->andReturn('polished prompt');
    // Step 4 first AI turn returns empty (non-quota provider failure per pin #5).
    $ai->shouldReceive('generateText')
        ->once()
        ->andReturn('');

    $this->actingAs($user);

    $component = Livewire::test(MeetYourAi::class)
        ->call('pickBusinessType', 'services')
        ->set('q1Offer', 'consulting')
        ->call('submitQuestion')
        ->set('q2TopQuestion', 'how much?')
        ->call('submitQuestion')
        ->set('q3Tone', 'friendly')
        ->call('submitQuestion')
        ->call('generate')
        ->call('generateAiTurn');

    // No assistant message was appended — pin #5 forbids fake apology replies.
    $messages = $component->get('chatMessages');
    expect(collect($messages)->where('role', 'assistant'))->toHaveCount(0);
    expect($component->get('chatSoftError'))->not->toBe('');
});

it('validates Q1 requires at least 5 characters', function () {
    [$user] = makeMeetAiUser();
    $this->actingAs($user);

    Livewire::test(MeetYourAi::class)
        ->call('pickBusinessType', 'services')
        ->set('q1Offer', 'hi')
        ->call('submitQuestion')
        ->assertHasErrors(['q1Offer'])
        ->assertSet('questionIndex', 1);
});

it('caps the chat at MAX_CHAT_TURNS user messages', function () {
    [$user, $team] = makeMeetAiUser();
    $ai = bindAiMock();

    // Polish + 5 chat turns.
    $ai->shouldReceive('generateText')
        ->times(1 + MeetYourAi::MAX_CHAT_TURNS)
        ->andReturn('reply');

    $this->actingAs($user);

    $component = Livewire::test(MeetYourAi::class)
        ->call('pickBusinessType', 'services')
        ->set('q1Offer', 'consulting services')
        ->call('submitQuestion')
        ->set('q2TopQuestion', 'how much?')
        ->call('submitQuestion')
        ->set('q3Tone', 'friendly')
        ->call('submitQuestion')
        ->call('generate')
        ->call('generateAiTurn'); // first AI reply auto-fires

    // We now have 1 user + 1 assistant. Send MAX-1 more user turns.
    for ($i = 0; $i < MeetYourAi::MAX_CHAT_TURNS - 1; $i++) {
        $component
            ->set('customerInput', 'Follow up message '.$i)
            ->call('sendCustomerMessage')
            ->call('generateAiTurn');
    }

    // One more attempt should be a no-op.
    $before = $component->get('chatMessages');
    $component
        ->set('customerInput', 'One too many')
        ->call('sendCustomerMessage');
    $after = $component->get('chatMessages');

    expect(count($after))->toBe(count($before));
});
