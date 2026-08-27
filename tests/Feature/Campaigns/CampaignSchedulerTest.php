<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Team;
use App\Services\Campaigns\CampaignScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CampaignSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_recipients_with_jittered_scheduled_at(): void
    {
        Carbon::setTestNow('2026-08-26 10:00:00');
        $team = Team::factory()->create();
        $campaign = Campaign::factory()->create([
            'team_id'            => $team->id,
            'platform'           => 'whatsapp',
            'jitter_min_seconds' => 30,
            'jitter_max_seconds' => 60,
            'status'             => 'draft',
        ]);

        $phones = ['+201000000001', '+201000000002', '+201000000003', '+201000000004', '+201000000005'];

        app(CampaignScheduler::class)->schedule($campaign, $phones, channel: 'whatsapp');

        $recipients = CampaignRecipient::where('campaign_id', $campaign->id)
            ->orderBy('scheduled_at')
            ->get();

        $this->assertCount(5, $recipients);
        $prev = null;
        foreach ($recipients as $r) {
            $this->assertSame('pending', $r->status);
            $this->assertSame('whatsapp', $r->channel);
            $this->assertNotNull($r->phone);
            if ($prev) {
                $gap = abs($r->scheduled_at->diffInSeconds($prev));
                $this->assertGreaterThanOrEqual(30, $gap);
                $this->assertLessThanOrEqual(60, $gap);
            }
            $prev = $r->scheduled_at;
        }
    }

    public function test_claim_next_batch_returns_at_most_ceiling_rows_and_marks_them_queued(): void
    {
        $campaign = Campaign::factory()->create(['status' => 'active', 'platform' => 'whatsapp']);
        CampaignRecipient::factory()->count(60)->create([
            'campaign_id'  => $campaign->id,
            'status'       => 'pending',
            'channel'      => 'whatsapp',
            'scheduled_at' => now()->subMinute(),
        ]);

        $claimed = app(CampaignScheduler::class)->claimNextBatch(50);

        $this->assertLessThanOrEqual(50, $claimed->count());
        $this->assertGreaterThan(0, $claimed->count());
        foreach ($claimed as $r) {
            $this->assertSame('queued', $r->status);
        }
    }

    public function test_claim_returns_empty_when_nothing_eligible(): void
    {
        $campaign = Campaign::factory()->create(['status' => 'active', 'platform' => 'whatsapp']);
        CampaignRecipient::factory()->count(5)->create([
            'campaign_id'  => $campaign->id,
            'status'       => 'pending',
            'channel'      => 'whatsapp',
            'scheduled_at' => now()->addHour(), // future
        ]);

        $claimed = app(CampaignScheduler::class)->claimNextBatch(50);

        $this->assertTrue($claimed->isEmpty());
    }
}
