<?php

declare(strict_types=1);

use App\Models\Referral;
use App\Models\Team;
use App\Services\Referrals\ReferralService;

beforeEach(function () {
    $this->service = app(ReferralService::class);
});

test('a team gets exactly one active referral (idempotent)', function () {
    $team = Team::factory()->create();

    $first = $this->service->getOrCreateForTeam($team);
    $second = $this->service->getOrCreateForTeam($team);

    expect($first->id)->toBe($second->id);
    expect(Referral::query()->where('referrer_team_id', $team->id)->count())->toBe(1);
});

test('code is unique across teams', function () {
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    $referralA = $this->service->getOrCreateForTeam($teamA);
    $referralB = $this->service->getOrCreateForTeam($teamB);

    expect($referralA->code)->not->toBe($referralB->code);
});

test('cannot redeem your own code', function () {
    $team = Team::factory()->create();
    $referral = $this->service->getOrCreateForTeam($team);

    $result = $this->service->redeem($referral->code, $team);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain("your own");
    expect($referral->fresh()->redeemed_at)->toBeNull();
});

test('cannot redeem an already-redeemed code', function () {
    $referrer = Team::factory()->create();
    $firstRedeemer = Team::factory()->create();
    $secondRedeemer = Team::factory()->create();

    $referral = $this->service->getOrCreateForTeam($referrer);

    $first = $this->service->redeem($referral->code, $firstRedeemer);
    expect($first['success'])->toBeTrue();

    $second = $this->service->redeem($referral->code, $secondRedeemer);
    expect($second['success'])->toBeFalse();
    expect($second['message'])->toContain('already been redeemed');
});

test('successful redemption snapshots current config discount values into the row', function () {
    config()->set('referrals.discount_percent', 30);
    config()->set('referrals.reciprocal_discount_percent', 15);

    $referrer = Team::factory()->create();
    $redeemer = Team::factory()->create();
    $referral = $this->service->getOrCreateForTeam($referrer);

    $result = $this->service->redeem($referral->code, $redeemer);

    expect($result['success'])->toBeTrue();
    expect($result['discount_percent'])->toEqual(30.0);

    $referral->refresh();
    expect((float) $referral->discount_percent)->toEqual(30.0);
    expect((float) $referral->reciprocal_discount_percent)->toEqual(15.0);
    expect($referral->referred_team_id)->toBe($redeemer->id);
    expect($referral->redeemed_at)->not->toBeNull();
});

test('reciprocal discount is granted to the referrer', function () {
    config()->set('referrals.discount_percent', 25);
    config()->set('referrals.reciprocal_discount_percent', 20);

    $referrer = Team::factory()->create();
    $redeemer = Team::factory()->create();
    $referral = $this->service->getOrCreateForTeam($referrer);

    $this->service->redeem($referral->code, $redeemer);

    $referrer->refresh();
    $redeemer->refresh();

    expect(data_get($referrer->settings, 'pending_referral_discount_percent'))->toEqual(20.0);
    expect(data_get($redeemer->settings, 'pending_referral_discount_percent'))->toEqual(25.0);
});

test('cannot redeem if you have already redeemed one before', function () {
    $referrerA = Team::factory()->create();
    $referrerB = Team::factory()->create();
    $redeemer = Team::factory()->create();

    $codeA = $this->service->getOrCreateForTeam($referrerA)->code;
    $codeB = $this->service->getOrCreateForTeam($referrerB)->code;

    $first = $this->service->redeem($codeA, $redeemer);
    expect($first['success'])->toBeTrue();

    $second = $this->service->redeem($codeB, $redeemer);
    expect($second['success'])->toBeFalse();
    expect($second['message'])->toContain('already redeemed');
});
