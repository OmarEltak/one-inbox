<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Phase G smoke test for the Terms of Service page.
 *
 * The "Credits, allowances, and refunds" section was added in Phase G to
 * document the two-balance credit economy and outage-refund rule (per
 * spec §7.2). This test guards that section from accidental removal.
 */

test('/terms loads with 200', function () {
    $this->get(route('terms'))->assertStatus(200);
});

test('/terms includes the Credits, allowances, and refunds section', function () {
    $response = $this->get(route('terms'));

    $response->assertStatus(200);
    $response->assertSeeText('Credits, allowances, and refunds');
    $response->assertSeeText('monthly AI-credit allowance');
});
