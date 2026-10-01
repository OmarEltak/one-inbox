<?php

declare(strict_types=1);

use App\Jobs\SyncPageConversations;
use App\Livewire\Connections\Index as ConnectionsIndex;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Platforms\FacebookPlatform;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Regression pins for "I connected Instagram and receive no messages" (2026-10-01).
 *
 * 1. The Direct (IG Login) button is the only IG path that receives customer DMs
 *    while the main Meta app is on Standard Access — it must stay on the card.
 * 2. Business Login tokens lack instagram_business_manage_comments, so subscribing
 *    to `comments` makes Meta reject the whole call and `messages` never lands.
 * 3. A failed subscription must not be reported as a successful connection.
 */

/**
 * @return array{0: User, 1: Team}
 */
function makeIgOwner(bool $superAdmin = false): array
{
    $user = User::factory()->create(['is_super_admin' => $superAdmin]);
    $team = Team::factory()->create(['owner_id' => $user->id]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return [$user->fresh(), $team];
}

function fakeInstagramOAuth(int $subscribeStatus): void
{
    Http::fake([
        'api.instagram.com/oauth/access_token' => Http::response([
            'access_token' => 'short-token',
            'user_id' => '17841400000000001',
        ]),
        'graph.instagram.com/access_token*' => Http::response([
            'access_token' => 'long-token',
            'expires_in' => 5184000,
        ]),
        'graph.instagram.com/me*' => Http::response([
            'id' => '27380000000000001',
            'user_id' => '17841400000000001',
            'username' => 'acme_store',
            'name' => 'Acme Store',
        ]),
        'graph.instagram.com/*/subscribed_apps' => $subscribeStatus === 200
            ? Http::response(['success' => true])
            : Http::response(['error' => ['message' => 'Application does not have the capability to make this API call.', 'code' => 3]], $subscribeStatus),
    ]);
}

beforeEach(function () {
    config([
        'services.meta.app_id' => 'test-app-id',
        'services.meta.app_secret' => 'test-app-secret',
        'services.meta.instagram_app_id' => 'test-ig-app-id',
        'services.meta.instagram_app_secret' => 'test-ig-secret',
    ]);
});

it('shows the Direct (IG Login) button to super-admins while the app is unapproved', function () {
    config(['services.meta.app_verified' => false]);

    [$user] = makeIgOwner(superAdmin: true);
    $this->actingAs($user);

    Livewire::test(ConnectionsIndex::class)
        ->assertSeeHtml(route('connections.instagram.redirect'))
        ->assertSeeHtml(route('connections.instagram-via-facebook.redirect'))
        ->assertSee('Connect Direct (IG Login)')
        ->assertSee('only receives DMs from app testers');
});

it('subscribes Business Login pages to messages only', function () {
    Http::fake(['graph.instagram.com/*' => Http::response(['success' => true])]);

    $page = Page::factory()->create([
        'platform' => 'instagram',
        'platform_page_id' => '17841400000000001',
        'page_access_token' => 'long-token',
    ]);

    expect(app(FacebookPlatform::class)->subscribeInstagramPage($page))->toBeTrue();

    Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/17841400000000001/subscribed_apps')
        && $r['subscribed_fields'] === 'messages');
});

it('reports success when the Instagram message subscription succeeds', function () {
    Bus::fake([SyncPageConversations::class]);
    fakeInstagramOAuth(200);

    [$user, $team] = makeIgOwner(superAdmin: true);

    $this->actingAs($user)
        ->get(route('connections.instagram.callback', ['code' => 'abc']))
        ->assertRedirect(route('connections.index'))
        ->assertSessionHas('success');

    $page = Page::where('team_id', $team->id)->where('platform', 'instagram')->sole();
    expect($page->is_active)->toBeTrue()
        ->and($page->platform_page_id)->toBe('17841400000000001')
        ->and($page->metadata)->not->toHaveKey('subscription_error');
});

it('does not report success when Meta refuses the Instagram message subscription', function () {
    Bus::fake([SyncPageConversations::class]);
    fakeInstagramOAuth(400);

    [$user, $team] = makeIgOwner(superAdmin: true);

    $this->actingAs($user)
        ->get(route('connections.instagram.callback', ['code' => 'abc']))
        ->assertRedirect(route('connections.index'))
        ->assertSessionMissing('success')
        ->assertSessionHas('error');

    $page = Page::where('team_id', $team->id)->where('platform', 'instagram')->sole();
    expect($page->metadata['subscription_error'] ?? null)->toBe('subscribe_failed');
});
