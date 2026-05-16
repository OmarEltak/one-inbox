<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Services\Email\CampaignDispatcher;

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();
    $this->page = makeEmailPage($this->team);
});

test('schedules a recipient row per matching contact', function () {
    foreach (range(1, 5) as $i) {
        Contact::create([
            'team_id' => $this->team->id,
            'email'   => "user{$i}@x.com",
            'name'    => "User {$i}",
            'tags'    => ['imported:list-a'],
        ]);
    }

    $campaign = Campaign::create([
        'team_id'            => $this->team->id,
        'created_by'         => $this->user->id,
        'platform'           => 'email',
        'name'               => 'Test',
        'type'               => 'broadcast',
        'subject'            => 'Hi',
        'message_template'   => 'Hello {{name}}',
        'sender_page_id'     => $this->page->id,
        'daily_cap'          => 200,
        'jitter_min_seconds' => 0,
        'jitter_max_seconds' => 0,
        'target_criteria'    => ['contact_tag' => 'imported:list-a'],
        'status'             => Campaign::STATUS_ACTIVE,
    ]);

    $scheduled = (new CampaignDispatcher())->schedule($campaign);

    expect($scheduled)->toBe(5);
    expect($campaign->refresh()->total_contacts)->toBe(5);
    expect(CampaignRecipient::where('campaign_id', $campaign->id)->count())->toBe(5);
});

test('rolls schedule into next day when daily cap is exceeded', function () {
    foreach (range(1, 3) as $i) {
        Contact::create([
            'team_id' => $this->team->id,
            'email'   => "u{$i}@x.com",
            'tags'    => ['imported:cap-list'],
        ]);
    }

    $campaign = Campaign::create([
        'team_id'            => $this->team->id,
        'created_by'         => $this->user->id,
        'platform'           => 'email',
        'name'               => 'Capped',
        'type'               => 'broadcast',
        'subject'            => 'Hi',
        'message_template'   => 'Hello',
        'sender_page_id'     => $this->page->id,
        'daily_cap'          => 2,
        'jitter_min_seconds' => 0,
        'jitter_max_seconds' => 0,
        'target_criteria'    => ['contact_tag' => 'imported:cap-list'],
        'status'             => Campaign::STATUS_ACTIVE,
    ]);

    (new CampaignDispatcher())->schedule($campaign);

    $scheduledAts = CampaignRecipient::where('campaign_id', $campaign->id)
        ->orderBy('id')
        ->pluck('scheduled_at');

    expect($scheduledAts)->toHaveCount(3);
    // First two are today; third is tomorrow or later.
    $today = now()->startOfDay();
    expect($scheduledAts[0]->lt($today->copy()->addDay()))->toBeTrue();
    expect($scheduledAts[2]->gte($today->copy()->addDay()))->toBeTrue();
});

test('is idempotent on re-run (no duplicate recipients)', function () {
    Contact::create([
        'team_id' => $this->team->id,
        'email'   => 'a@x.com',
        'tags'    => ['imported:idem'],
    ]);

    $campaign = Campaign::create([
        'team_id'            => $this->team->id,
        'created_by'         => $this->user->id,
        'platform'           => 'email',
        'name'               => 'Idem',
        'type'               => 'broadcast',
        'subject'            => 'Hi',
        'message_template'   => '',
        'sender_page_id'     => $this->page->id,
        'daily_cap'          => 10,
        'jitter_min_seconds' => 0,
        'jitter_max_seconds' => 0,
        'target_criteria'    => ['contact_tag' => 'imported:idem'],
        'status'             => Campaign::STATUS_ACTIVE,
    ]);

    $d = new CampaignDispatcher();
    $d->schedule($campaign);
    $d->schedule($campaign);

    expect(CampaignRecipient::where('campaign_id', $campaign->id)->count())->toBe(1);
});
