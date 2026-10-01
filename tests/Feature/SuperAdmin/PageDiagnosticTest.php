<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;

function diagUser(bool $superAdmin): User
{
    $user = User::factory()->create(['is_super_admin' => $superAdmin]);
    $team = Team::factory()->create(['owner_id' => $user->id]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return $user->fresh();
}

function igWebhook(string $entryId, ?int $teamId): WebhookLog
{
    return WebhookLog::create([
        'platform'   => 'instagram',
        'event_type' => 'instagram',
        'team_id'    => $teamId,
        'payload'    => ['object' => 'instagram', 'entry' => [[
            'id'        => $entryId,
            'messaging' => [['sender' => ['id' => '999'], 'recipient' => ['id' => $entryId], 'message' => ['mid' => 'm-'.$entryId, 'text' => 'hi']]],
        ]]],
    ]);
}

beforeEach(function () {
    config([
        'services.meta.app_id'               => 'main-app',
        'services.meta.app_secret'           => 'main-secret',
        'services.meta.instagram_app_id'     => 'ig-app',
        'services.meta.instagram_app_secret' => 'ig-secret',
    ]);
    Http::fake(['*' => Http::response(['data' => [['name' => 'OT1', 'subscribed_fields' => ['messages']]]])]);
});

it('is forbidden for non-super-admins', function () {
    $page = Page::factory()->create(['platform' => 'instagram']);

    $this->actingAs(diagUser(false))
        ->get(route('super-admin.pages.diagnose', $page))
        ->assertForbidden();
});

it('reports the page, its webhooks and unrouted instagram webhooks without leaking tokens', function () {
    $page = Page::factory()->create([
        'platform'          => 'instagram',
        'platform_page_id'  => '17841400000000022',
        'page_access_token' => 'SECRET-PAGE-TOKEN',
        'metadata'          => ['auth_type' => 'instagram_business', 'igbid' => '17841400000000022'],
    ]);
    igWebhook('17841400000000022', $page->team_id);   // routed to this page
    igWebhook('17841499999999999', null);             // arrived, routed nowhere

    $response = $this->actingAs(diagUser(true))
        ->get(route('super-admin.pages.diagnose', $page))
        ->assertOk()
        ->assertJsonPath('page.id', $page->id)
        ->assertJsonPath('page.has_token', true)
        ->assertJsonPath('webhooks.for_this_page_count', 1)
        ->assertJsonPath('webhooks.unrouted_instagram_entry_ids.17841499999999999', 1)
        ->assertJsonPath('meta.path', 'instagram_business_login (Instagram sub-app → /api/webhooks/meta-ig)');

    expect($response->getContent())
        ->not->toContain('SECRET-PAGE-TOKEN')
        ->not->toContain('main-secret')
        ->not->toContain('ig-secret');
});

it('explains why a stored conversation is hidden from the owner inbox and whether the viewer can see the page', function () {
    $page = Page::factory()->create(['platform' => 'instagram', 'platform_page_id' => '17841400000000077']);
    $contact = \App\Models\Contact::create(['team_id' => $page->team_id, 'name' => 'Lina']);
    \App\Models\Conversation::create([
        'page_id' => $page->id, 'team_id' => $page->team_id, 'platform' => 'instagram',
        'platform_conversation_id' => 'p1', 'contact_id' => $contact->id, 'status' => 'open',
        'sales_stage' => \App\Models\Conversation::STAGE_SPAM,
    ]);

    $this->actingAs(diagUser(true))   // super-admin on a different team than the page
        ->get(route('super-admin.pages.diagnose', $page))
        ->assertOk()
        ->assertJsonPath('viewer.can_see_page_in_own_inbox', false)
        ->assertJsonPath('conversations.latest.0.hidden_from_owner_inbox_because.0', 'sales_stage=spam (only visible under the Spam filter)');
});
