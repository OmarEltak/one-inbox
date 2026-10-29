<?php

declare(strict_types=1);

use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Regression pins for `ig-direct:refresh-tokens`:
 *
 *  - Only direct IG Business Login pages are considered; FB and IG-via-FB are ignored.
 *  - Already-expired tokens are skipped (reconnect required, refresh wouldn't help).
 *  - A successful refresh updates BOTH Page.page_access_token and
 *    ConnectedAccount.{access_token, token_expires_at}, and clears any stale
 *    refresh_error / subscription_error.
 *  - A failed refresh NEVER blanks the existing token; it writes metadata.refresh_error.
 *  - A failed refresh with <= 2 days left also writes metadata.subscription_error
 *    so the UI shows a reconnect CTA before the cliff.
 */

function makePageForRefresh(array $pageOverrides = [], array $accountOverrides = []): Page
{
    $user = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $user->id]);

    $account = ConnectedAccount::create(array_merge([
        'team_id'          => $team->id,
        'platform'         => 'instagram',
        'platform_user_id' => '17841400000000001',
        'name'             => 'Acme Store',
        'access_token'     => 'old-token',
        'token_expires_at' => now()->addDays(10),
        'scopes'           => ['instagram_business_basic'],
        'is_active'        => true,
        'connected_at'     => now()->subDays(50),
    ], $accountOverrides));

    return Page::create(array_merge([
        'team_id'              => $team->id,
        'platform'             => 'instagram',
        'platform_page_id'     => '17841400000000001',
        'connected_account_id' => $account->id,
        'name'                 => 'Acme Store',
        'page_access_token'    => 'old-token',
        'category'             => 'instagram_business',
        'is_active'            => true,
        'metadata'             => ['auth_type' => 'instagram_business'],
    ], $pageOverrides));
}

it('ignores Facebook pages and IG-via-FB pages', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $user->id]);
    $account = ConnectedAccount::create([
        'team_id'          => $team->id,
        'platform'         => 'facebook',
        'platform_user_id' => '900000000000001',
        'name'             => 'Acme Owner',
        'access_token'     => 'fb-token',
        'token_expires_at' => now()->addDays(5),
        'scopes'           => [],
        'is_active'        => true,
        'connected_at'     => now()->subDays(55),
    ]);
    $fbPage = Page::create([
        'team_id'              => $team->id,
        'platform'             => 'facebook',
        'platform_page_id'     => '100000000000001',
        'connected_account_id' => $account->id,
        'name'                 => 'Acme FB',
        'page_access_token'    => 'fb-page-token',
        'category'             => 'business',
        'is_active'            => true,
        'metadata'             => [],
    ]);
    $igViaFbPage = Page::create([
        'team_id'              => $team->id,
        'platform'             => 'instagram',
        'platform_page_id'     => '17841400000000099',
        'connected_account_id' => $account->id,
        'name'                 => 'Acme IG via FB',
        'page_access_token'    => 'igvia-token',
        'category'             => 'instagram_business',
        'is_active'            => true,
        'metadata'             => ['linked_facebook_page_id' => '100000000000001'],
    ]);

    Http::fake();

    $this->artisan('ig-direct:refresh-tokens', ['--sleep-ms' => 0])->assertExitCode(0);

    Http::assertNothingSent();
    expect($fbPage->fresh()->page_access_token)->toBe('fb-page-token')
        ->and($igViaFbPage->fresh()->page_access_token)->toBe('igvia-token');
});

it('skips already-expired tokens (reconnect required)', function () {
    $page = makePageForRefresh([], [
        'token_expires_at' => now()->subDay(),
    ]);

    Http::fake();

    $this->artisan('ig-direct:refresh-tokens', ['--sleep-ms' => 0])->assertExitCode(0);

    Http::assertNothingSent();
    expect($page->fresh()->page_access_token)->toBe('old-token');
});

it('skips tokens still comfortably outside the refresh window', function () {
    $page = makePageForRefresh([], [
        'token_expires_at' => now()->addDays(45),
    ]);

    Http::fake();

    $this->artisan('ig-direct:refresh-tokens', ['--sleep-ms' => 0])->assertExitCode(0);

    Http::assertNothingSent();
    expect($page->fresh()->page_access_token)->toBe('old-token');
});

it('refreshes an eligible token and updates both Page and ConnectedAccount, clearing stale errors', function () {
    $page = makePageForRefresh([
        'metadata' => [
            'auth_type'          => 'instagram_business',
            'refresh_error'      => ['code' => 'prev', 'message' => 'old'],
            'subscription_error' => ['code' => 190, 'message' => 'old'],
        ],
    ]);

    Http::fake([
        '*graph.instagram.com/*/refresh_access_token*' => Http::response([
            'access_token' => 'new-fresh-token',
            'expires_in'   => 5184000,
            'token_type'   => 'bearer',
        ]),
    ]);

    $this->artisan('ig-direct:refresh-tokens', ['--sleep-ms' => 0])->assertExitCode(0);

    $page->refresh();
    expect($page->page_access_token)->toBe('new-fresh-token')
        ->and($page->metadata['refresh_error'] ?? null)->toBeNull()
        ->and($page->metadata['subscription_error'] ?? null)->toBeNull();

    $account = ConnectedAccount::find($page->connected_account_id);
    expect($account->access_token)->toBe('new-fresh-token')
        ->and($account->token_expires_at->isAfter(now()->addDays(55)))->toBeTrue();
});

it('does NOT blank the existing token when the refresh call fails', function () {
    $page = makePageForRefresh([], [
        'token_expires_at' => now()->addDays(10),
    ]);

    Http::fake([
        '*graph.instagram.com/*/refresh_access_token*' => Http::response([
            'error' => ['message' => 'Service temporarily unavailable', 'code' => 2],
        ], 500),
    ]);

    $this->artisan('ig-direct:refresh-tokens', ['--sleep-ms' => 0])->assertExitCode(0);

    $page->refresh();
    expect($page->page_access_token)->toBe('old-token')
        ->and($page->metadata['refresh_error']['code'])->toBe('2')
        ->and($page->metadata['subscription_error'] ?? null)->toBeNull(); // 10 days left > 2, no escalation
});

it('escalates to subscription_error when refresh fails inside the critical margin (<= 2 days left)', function () {
    $page = makePageForRefresh([], [
        'token_expires_at' => now()->addHours(36), // 1.5 days left
    ]);

    Http::fake([
        '*graph.instagram.com/*/refresh_access_token*' => Http::response([
            'error' => ['message' => 'OAuthException', 'code' => 190],
        ], 400),
    ]);

    $this->artisan('ig-direct:refresh-tokens', ['--sleep-ms' => 0])->assertExitCode(0);

    $page->refresh();
    expect($page->page_access_token)->toBe('old-token')
        ->and($page->metadata['refresh_error']['code'])->toBe('190')
        ->and($page->metadata['subscription_error']['source'])->toBe('ig_refresh');
});

it('--dry-run selects pages but makes no HTTP calls or writes', function () {
    $page = makePageForRefresh([], [
        'token_expires_at' => now()->addDays(5),
    ]);

    Http::fake();

    $this->artisan('ig-direct:refresh-tokens', ['--dry-run' => true, '--sleep-ms' => 0])
        ->expectsOutputToContain('would refresh')
        ->assertExitCode(0);

    Http::assertNothingSent();
    expect($page->fresh()->page_access_token)->toBe('old-token');
});

it('--page targets a specific page and bypasses the days-before-expiry filter', function () {
    // A page that is NOT within the normal 14-day window.
    $page = makePageForRefresh([], [
        'token_expires_at' => now()->addDays(50),
    ]);

    Http::fake([
        '*graph.instagram.com/*/refresh_access_token*' => Http::response([
            'access_token' => 'forced-refresh-token',
            'expires_in'   => 5184000,
        ]),
    ]);

    $this->artisan('ig-direct:refresh-tokens', ['--page' => $page->id, '--sleep-ms' => 0])
        ->assertExitCode(0);

    expect($page->fresh()->page_access_token)->toBe('forced-refresh-token');
});
