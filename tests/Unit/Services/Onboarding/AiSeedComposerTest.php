<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Exceptions\AiAllProvidersUnavailable;
use App\Exceptions\AiQuotaExhausted;
use App\Models\Team;
use App\Models\User;
use App\Services\Onboarding\AiSeedComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

// The composer is the boundary between our onboarding wizard and NaraRouter.
// Tests here pin CLAUDE.md pin #5: on ANY provider failure, we save the RAW
// template unchanged and NEVER emit "I apologize / having a moment" strings.

function makeTestTeam(): Team
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'     => 'Acme Bakery',
        'slug'     => 'team-'.Str::random(8),
        'owner_id' => $user->id,
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return $team->fresh();
}

it('builds the fixed template with every answer interpolated', function () {
    $ai = Mockery::mock(AiProviderInterface::class);
    $composer = new AiSeedComposer($ai);

    $template = $composer->buildTemplate([
        'business_name'   => 'Acme Bakery',
        'business_type'   => 'restaurant',
        'q1_offer'        => 'Fresh artisan sourdough delivered next-day',
        'q2_top_question' => 'Do you deliver to my area?',
        'q3_tone'         => 'friendly',
    ]);

    expect($template)
        ->toContain('Acme Bakery')
        ->toContain('Restaurant / F&B')
        ->toContain('Fresh artisan sourdough delivered next-day')
        ->toContain('Do you deliver to my area?')
        ->toContain('Friendly and helpful')
        ->toContain('never use emoji unless the customer does first');
});

it('handles missing business_type by falling back to Other', function () {
    $ai = Mockery::mock(AiProviderInterface::class);
    $composer = new AiSeedComposer($ai);

    $template = $composer->buildTemplate([
        'business_name'   => 'X Co',
        'q1_offer'        => 'stuff',
        'q2_top_question' => 'how much?',
    ]);

    expect($template)->toContain('Other');
});

it('polishes the prompt when NaraRouter returns a non-empty reply', function () {
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')
        ->once()
        ->andReturn("I'm the AI for Acme Bakery. We bake fresh sourdough daily and ship next-day.");

    $composer = new AiSeedComposer($ai);
    $team = makeTestTeam();

    $prompt = $composer->composeAndSave($team, [
        'business_name'   => 'Acme Bakery',
        'business_type'   => 'restaurant',
        'q1_offer'        => 'Fresh sourdough',
        'q2_top_question' => 'Do you deliver?',
        'q3_tone'         => 'friendly',
    ]);

    expect($prompt)->toContain("I'm the AI for Acme Bakery");

    $team->refresh();
    expect($team->settings['onboarding_ai_seed']['polished'])->toBeTrue();
    expect($team->settings['onboarding_ai_seed']['system_prompt'])->toBe($prompt);
});

it('falls back to raw template when NaraRouter throws AiQuotaExhausted', function () {
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')
        ->once()
        ->andThrow(new AiQuotaExhausted('quota reached'));

    $composer = new AiSeedComposer($ai);
    $team = makeTestTeam();

    $prompt = $composer->composeAndSave($team, [
        'business_name'   => 'Acme Bakery',
        'business_type'   => 'restaurant',
        'q1_offer'        => 'Fresh sourdough',
        'q2_top_question' => 'Do you deliver?',
        'q3_tone'         => 'friendly',
    ]);

    // Raw template MUST be persisted verbatim.
    expect($prompt)->toContain('You are the AI sales assistant for Acme Bakery');
    expect($prompt)->toContain('Fresh sourdough');
    // Anti-regression: never leak apology strings — CLAUDE.md pin #5.
    expect($prompt)
        ->not->toContain('apologize')
        ->not->toContain('having a moment')
        ->not->toContain('temporarily unavailable');

    $team->refresh();
    expect($team->settings['onboarding_ai_seed']['polished'])->toBeFalse();
});

it('falls back to raw template when NaraRouter throws AiAllProvidersUnavailable', function () {
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')
        ->once()
        ->andThrow(new AiAllProvidersUnavailable('cooldown active'));

    $composer = new AiSeedComposer($ai);
    $team = makeTestTeam();

    $prompt = $composer->composeAndSave($team, [
        'business_name'   => 'Acme Bakery',
        'q1_offer'        => 'x',
        'q2_top_question' => 'y',
    ]);

    expect($prompt)->toContain('Acme Bakery');
    expect($prompt)->not->toContain('apologize');
});

it('falls back to raw template when NaraRouter returns an empty string', function () {
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('');

    $composer = new AiSeedComposer($ai);
    $team = makeTestTeam();

    $prompt = $composer->composeAndSave($team, [
        'business_name'   => 'Acme Bakery',
        'q1_offer'        => 'a',
        'q2_top_question' => 'b',
    ]);

    // Empty polish reply → raw template used.
    expect($prompt)->toContain('You are the AI sales assistant for Acme Bakery');
    $team->refresh();
    expect($team->settings['onboarding_ai_seed']['polished'])->toBeFalse();
});

it('falls back to raw template when NaraRouter throws an unexpected exception', function () {
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andThrow(new RuntimeException('network died'));

    $composer = new AiSeedComposer($ai);
    $team = makeTestTeam();

    $prompt = $composer->composeAndSave($team, [
        'business_name'   => 'Acme',
        'q1_offer'        => 'x',
        'q2_top_question' => 'y',
    ]);

    expect($prompt)->toContain('You are the AI sales assistant for Acme');
});

it('strips markdown fences and wrapping quotes from polished replies', function () {
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn("```\n\"Hi, I'm the AI.\"\n```");

    $composer = new AiSeedComposer($ai);
    $team = makeTestTeam();

    $prompt = $composer->composeAndSave($team, [
        'business_name'   => 'Acme',
        'q1_offer'        => 'x',
        'q2_top_question' => 'y',
    ]);

    expect($prompt)->toBe("Hi, I'm the AI.");
});

it('exposes deterministic preset questions per business type', function () {
    expect(AiSeedComposer::presetQuestionsFor('ecommerce'))
        ->toBeArray()
        ->toContain('Is this in stock?');
    expect(AiSeedComposer::presetQuestionsFor('restaurant'))
        ->toContain('Do you deliver to my area?');
    expect(AiSeedComposer::presetQuestionsFor('unknown-type'))
        ->toBeArray()
        ->not->toBeEmpty();
});
