<?php

declare(strict_types=1);

use App\Livewire\DisconnectedPagesNotice;
use App\Models\Page;
use Livewire\Livewire;

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();
    $this->actingAs($this->user);
});

/**
 * Helper: build a Page with the modern subscription_error array shape written
 * by PageHealthCheckCommand. first_seen_at defaults to now so that tests which
 * don't dismiss see it rendered.
 */
function makeBrokenPage($team, string $name, ?string $firstSeenAt = null, ?string $dismissedAt = null): Page
{
    $meta = [
        'subscription_error' => [
            'code'          => 190,
            'message'       => 'Invalid OAuth access token - Cannot parse access token',
            'source'        => 'debug_token',
            'first_seen_at' => $firstSeenAt ?? now()->toIso8601String(),
            'last_seen_at'  => now()->toIso8601String(),
        ],
    ];
    if ($dismissedAt !== null) {
        $meta['reconnect_notice_dismissed_at'] = $dismissedAt;
    }

    $account = \App\Models\ConnectedAccount::create([
        'team_id'          => $team->id,
        'platform'         => 'instagram',
        'platform_user_id' => 'IGU-'.$name,
        'name'             => $name,
        'access_token'     => encrypt('t'),
        'scopes'           => ['instagram_manage_messages'],
        'is_active'        => true,
        'connected_at'     => now(),
    ]);

    return Page::create([
        'connected_account_id' => $account->id,
        'team_id'              => $team->id,
        'platform'             => 'instagram',
        'platform_page_id'     => 'IGP-'.$name,
        'name'                 => $name,
        'is_active'            => true,
        'metadata'             => $meta,
    ]);
}

test('renders a nudge for each broken active FB/IG page on the current team', function () {
    makeBrokenPage($this->team, 'Eagle Prime');
    makeBrokenPage($this->team, 'Brandk');

    Livewire::test(DisconnectedPagesNotice::class)
        ->assertSee('Eagle Prime')
        ->assertSee('Brandk');
});

test('does not render pages belonging to other teams (tenant isolation)', function () {
    [, $otherTeam] = makeUserWithTeam();
    makeBrokenPage($otherTeam, 'Not Mine');

    Livewire::test(DisconnectedPagesNotice::class)
        ->assertDontSee('Not Mine');
});

test('acknowledge() hides the nudge for the acked page and keeps others visible', function () {
    $kept = makeBrokenPage($this->team, 'StillBroken');
    $acked = makeBrokenPage($this->team, 'ClickedDismiss');

    Livewire::test(DisconnectedPagesNotice::class)
        ->assertSee('StillBroken')
        ->assertSee('ClickedDismiss')
        ->call('acknowledge', $acked->id)
        ->assertSee('StillBroken')
        ->assertDontSee('ClickedDismiss');
});

test('a dismissal does NOT silence a later re-break (new first_seen_at)', function () {
    // Page was broken Mon, dismissed Tue, then PageHealthCheck detected a fresh
    // break Wed. The nudge must come back — otherwise one click permanently
    // mutes a page forever.
    makeBrokenPage(
        team: $this->team,
        name: 'ReBrokenPage',
        firstSeenAt: now()->subDays(1)->toIso8601String(),  // detected AFTER dismissal
        dismissedAt: now()->subDays(2)->toIso8601String(),  // older dismiss
    );

    Livewire::test(DisconnectedPagesNotice::class)
        ->assertSee('ReBrokenPage');
});

test('ignores pages that are no longer active', function () {
    $page = makeBrokenPage($this->team, 'DeactivatedPage');
    $page->update(['is_active' => false]);

    Livewire::test(DisconnectedPagesNotice::class)
        ->assertDontSee('DeactivatedPage');
});
