<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\EmailSuppression;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();
    $this->page = makeEmailPage($this->team);
    $this->campaign = Campaign::create([
        'team_id'            => $this->team->id,
        'created_by'         => $this->user->id,
        'platform'           => 'email',
        'name'               => 'X',
        'type'               => 'broadcast',
        'subject'            => 'x',
        'message_template'   => '',
        'sender_page_id'     => $this->page->id,
        'daily_cap'          => 200,
        'jitter_min_seconds' => 0,
        'jitter_max_seconds' => 0,
        'status'             => Campaign::STATUS_ACTIVE,
    ]);
});

test('open pixel marks sent recipient as opened', function () {
    $r = CampaignRecipient::create([
        'campaign_id' => $this->campaign->id,
        'email'       => 'a@x.com',
        'status'      => CampaignRecipient::STATUS_SENT,
        'sent_at'     => now()->subMinute(),
    ]);

    $url = URL::signedRoute('email.track.open', ['recipient' => $r->id]);

    $response = $this->get($url);
    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/gif');

    $r->refresh();
    expect($r->status)->toBe(CampaignRecipient::STATUS_OPENED);
    expect($r->opened_at)->not->toBeNull();
    expect($this->campaign->refresh()->opened_count)->toBe(1);
});

test('unsubscribe confirm adds suppression and marks recipient', function () {
    $r = CampaignRecipient::create([
        'campaign_id' => $this->campaign->id,
        'email'       => 'b@x.com',
        'status'      => CampaignRecipient::STATUS_SENT,
        'sent_at'     => now()->subMinute(),
    ]);

    $url = URL::signedRoute('email.unsubscribe.confirm', ['recipient' => $r->id]);

    $this->post($url)->assertOk();

    expect(EmailSuppression::isSuppressed($this->team->id, 'b@x.com'))->toBeTrue();
    expect($r->refresh()->status)->toBe(CampaignRecipient::STATUS_UNSUBSCRIBED);
    expect($this->campaign->refresh()->unsubscribed_count)->toBe(1);
});

test('rejects unsigned requests', function () {
    $r = CampaignRecipient::create([
        'campaign_id' => $this->campaign->id,
        'email'       => 'c@x.com',
        'status'      => CampaignRecipient::STATUS_SENT,
    ]);

    $this->get(route('email.track.open', ['recipient' => $r->id]))->assertForbidden();
});
