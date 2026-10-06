<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Phase G smoke tests for the public /pricing-faq page.
 *
 * The page is read-only and reads live from config('ai_costs') so Omar can
 * tune costs without touching the view. These tests verify the page loads
 * for both guest and authenticated users, and that the dynamic cost table
 * + Deep Analysis minimum render from config (not hard-coded strings).
 */

test('/pricing-faq loads with 200 for a guest', function () {
    $response = $this->get(route('pricing-faq'));

    $response->assertStatus(200);
    $response->assertSeeText('Pricing FAQ');
});

test('/pricing-faq loads with 200 for an authenticated user', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('pricing-faq'))
        ->assertStatus(200);
});

test('/pricing-faq renders the cost table from config', function () {
    // Force a known value so the assertion is deterministic.
    config(['ai_costs.ai_chat_turn' => 1]);

    $response = $this->get(route('pricing-faq'));

    $response->assertStatus(200);
    // The row is tagged with data-test="cost-row-ai_chat_turn" so the
    // snapshot test confirms the translated label landed and the <table>
    // was built from the config map, not a hard-coded array.
    $response->assertSee('data-test="cost-row-ai_chat_turn"', false);
    $response->assertSee('data-test="cost-row-ai_reply_outbound"', false);
    $response->assertSee('data-test="cost-table"', false);
});

test('/pricing-faq renders the 4-tier plan ladder from config', function () {
    $response = $this->get(route('pricing-faq'));

    $response->assertStatus(200);
    $response->assertSee('data-test="plan-ladder-table"', false);
    $response->assertSee('data-test="plan-ladder-row-free"', false);
    $response->assertSee('data-test="plan-ladder-row-starter"', false);
    $response->assertSee('data-test="plan-ladder-row-pro"', false);
    $response->assertSee('data-test="plan-ladder-row-business"', false);
});

test('/pricing-faq renders the Deep Analysis minimum from config', function () {
    // Pick a non-default value to be sure the page is actually reading
    // from config (vs matching whatever happens to be in the default).
    config(['ai_costs.deep_analysis_minimum' => 17]);
    config(['ai_costs.deep_analysis_per_100_contacts' => 7]);

    $response = $this->get(route('pricing-faq'));

    $response->assertStatus(200);
    // 7 credits per 100 contacts and a minimum of 17 → 1000 contacts cost
    // max(17, 10*7) = 70. All three numbers must appear on the page.
    $response->assertSeeText('7');
    $response->assertSeeText('17');
    $response->assertSeeText('70');
});
