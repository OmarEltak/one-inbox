<?php

declare(strict_types=1);

use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Regression pins for the "Direct IG Login shows Needs reconnect every hour"
 * loop (2026-10-29). Direct Instagram Business Login tokens are issued by a
 * SEPARATE Meta app (services.meta.instagram_app_id/_secret) and must be
 * validated against graph.instagram.com — NOT graph.facebook.com with the
 * Facebook app's secret, which returns "Cannot parse access token" for every
 * one of them. That false positive forced every Direct-IG customer to
 * reconnect ~1h after each reconnect.
 */

function makeDirectIgPage(array $metadataOverrides = []): Page
{
    $user = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $user->id]);

    $account = ConnectedAccount::create([
        'team_id'          => $team->id,
        'platform'         => 'instagram',
        'platform_user_id' => '17841400000000001',
        'name'             => 'Acme Store',
        'access_token'     => 'ig-long-lived-token',
        'token_expires_at' => now()->addDays(60),
        'scopes'           => ['instagram_business_basic', 'instagram_business_manage_messages'],
        'is_active'        => true,
        'connected_at'     => now(),
    ]);

    return Page::create([
        'team_id'              => $team->id,
        'platform'             => 'instagram',
        'platform_page_id'     => '17841400000000001',
        'connected_account_id' => $account->id,
        'name'                 => 'Acme Store',
        'page_access_token'    => 'ig-long-lived-token',
        'category'             => 'instagram_business',
        'is_active'            => true,
        'metadata'             => array_merge([
            'auth_type' => 'instagram_business',
            'igbid'     => '17841400000000001',
        ], $metadataOverrides),
    ]);
}

beforeEach(function () {
    config([
        'services.meta.app_id'               => 'fb-app-id',
        'services.meta.app_secret'           => 'fb-app-secret',
        'services.meta.instagram_app_id'     => 'ig-app-id',
        'services.meta.instagram_app_secret' => 'ig-app-secret',
        'services.meta.graph_api_version'    => 'v21.0',
    ]);
});

it('does NOT call graph.facebook.com/debug_token for Direct IG Login pages', function () {
    $page = makeDirectIgPage();

    // Note: Meta returns an INTERNAL subscription-binding id in the response
    // (17-digit object id), NOT our dev-console instagram_app_id. The health
    // check must not compare against instagram_app_id — pin for the secondary
    // false positive uncovered on 2026-10-29.
    Http::fake([
        'graph.instagram.com/*/me*' => Http::response([
            'user_id'  => '17841400000000001',
            'username' => 'acme_store',
        ]),
        'graph.instagram.com/*/subscribed_apps*' => Http::response([
            'data' => [[
                'id' => '17989107939061961',
                'subscribed_fields' => ['messages', 'comments'],
            ]],
        ]),
    ]);

    $this->artisan('pages:health-check', ['--page' => $page->id])->assertExitCode(0);

    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'graph.facebook.com'));
    Http::assertSent(fn ($req) => str_contains($req->url(), 'graph.instagram.com'));

    expect($page->fresh()->metadata['subscription_error'] ?? null)->toBeNull();
});

it('clears a previously-written false-positive subscription_error for Direct IG pages whose tokens are actually valid', function () {
    $page = makeDirectIgPage([
        'subscription_error' => [
            'code' => 190,
            'message' => 'Invalid OAuth access token - Cannot parse access token',
            'source' => 'debug_token',
            'first_seen_at' => now()->subHour()->toIso8601String(),
            'last_seen_at' => now()->subMinute()->toIso8601String(),
        ],
    ]);

    Http::fake([
        'graph.instagram.com/*/me*' => Http::response([
            'user_id'  => '17841400000000001',
            'username' => 'acme_store',
        ]),
        'graph.instagram.com/*/subscribed_apps*' => Http::response([
            'data' => [[
                'id' => '17989107939061961',
                'subscribed_fields' => ['messages', 'comments'],
            ]],
        ]),
    ]);

    $this->artisan('pages:health-check', ['--page' => $page->id])->assertExitCode(0);

    expect($page->fresh()->metadata['subscription_error'] ?? null)->toBeNull();
});

it('reports a Direct IG Login token as broken when graph.instagram.com rejects it with code 190', function () {
    $page = makeDirectIgPage();

    Http::fake([
        'graph.instagram.com/*/me*' => Http::response([
            'error' => [
                'message' => 'The access token could not be decrypted',
                'type'    => 'OAuthException',
                'code'    => 190,
            ],
        ], 400),
    ]);

    $this->artisan('pages:health-check', ['--page' => $page->id])->assertExitCode(0);

    $err = $page->fresh()->metadata['subscription_error'] ?? null;
    expect($err)->not->toBeNull()
        ->and($err['code'])->toBe(190)
        ->and($err['source'])->toBe('ig_me');
});

it('reports a Direct IG Login page as broken when `messages` is not in subscribed_fields', function () {
    $page = makeDirectIgPage();

    // Empty data array OR data without 'messages' both mean our app is not
    // subscribed for the inbox webhook. Test the realistic case: Meta returned
    // the subscription but somehow it dropped `messages`.
    Http::fake([
        'graph.instagram.com/*/me*' => Http::response([
            'user_id'  => '17841400000000001',
            'username' => 'acme_store',
        ]),
        'graph.instagram.com/*/subscribed_apps*' => Http::response([
            'data' => [[
                'id' => '17989107939061961',
                'subscribed_fields' => ['comments'],
            ]],
        ]),
    ]);

    $this->artisan('pages:health-check', ['--page' => $page->id])->assertExitCode(0);

    $err = $page->fresh()->metadata['subscription_error'] ?? null;
    expect($err)->not->toBeNull()
        ->and($err['code'])->toBe('not_subscribed')
        ->and($err['source'])->toBe('ig_subscribed_apps');
});

it('reports a Direct IG Login page as broken when subscribed_apps returns an empty data array', function () {
    $page = makeDirectIgPage();

    Http::fake([
        'graph.instagram.com/*/me*' => Http::response([
            'user_id'  => '17841400000000001',
            'username' => 'acme_store',
        ]),
        'graph.instagram.com/*/subscribed_apps*' => Http::response([
            'data' => [],
        ]),
    ]);

    $this->artisan('pages:health-check', ['--page' => $page->id])->assertExitCode(0);

    $err = $page->fresh()->metadata['subscription_error'] ?? null;
    expect($err)->not->toBeNull()
        ->and($err['code'])->toBe('not_subscribed')
        ->and($err['source'])->toBe('ig_subscribed_apps');
});

it('still validates Facebook pages against graph.facebook.com (the IG-business branch does not swallow FB)', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $user->id]);

    $account = ConnectedAccount::create([
        'team_id'          => $team->id,
        'platform'         => 'facebook',
        'platform_user_id' => '900000000000001',
        'name'             => 'Acme Owner',
        'access_token'     => 'fb-user-token',
        'token_expires_at' => now()->addDays(60),
        'scopes'           => ['pages_messaging', 'pages_show_list'],
        'is_active'        => true,
        'connected_at'     => now(),
    ]);

    $fbPage = Page::create([
        'team_id'              => $team->id,
        'platform'             => 'facebook',
        'platform_page_id'     => '100000000000001',
        'connected_account_id' => $account->id,
        'name'                 => 'Acme Page',
        'page_access_token'    => 'fb-page-token',
        'category'             => 'business',
        'is_active'            => true,
        'metadata'             => [],
    ]);

    Http::fake([
        'graph.facebook.com/*/debug_token*' => Http::response([
            'data' => ['is_valid' => true, 'app_id' => 'fb-app-id'],
        ]),
        'graph.facebook.com/*/subscribed_apps*' => Http::response([
            'data' => [['id' => 'fb-app-id']],
        ]),
    ]);

    $this->artisan('pages:health-check', ['--page' => $fbPage->id])->assertExitCode(0);

    Http::assertSent(fn ($req) => str_contains($req->url(), 'graph.facebook.com/') && str_contains($req->url(), 'debug_token'));
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'graph.instagram.com'));

    expect($fbPage->fresh()->metadata['subscription_error'] ?? null)->toBeNull();
});
