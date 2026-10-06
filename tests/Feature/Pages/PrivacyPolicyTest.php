<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Phase G smoke test for the Privacy Policy page.
 *
 * The payment-data section was added in Phase G to document that no card
 * details are stored (per spec §7.1). This test guards that section from
 * being accidentally removed in a future legal copy pass.
 */

test('/privacy loads with 200', function () {
    $this->get(route('privacy'))->assertStatus(200);
});

test('/privacy includes the Payment data section', function () {
    $response = $this->get(route('privacy'));

    $response->assertStatus(200);
    $response->assertSeeText('Payment data');
    $response->assertSeeText('We do not accept card payments');
});
