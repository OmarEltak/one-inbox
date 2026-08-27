<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Jobs\SendCampaignEmailJob;
use App\Jobs\SendCampaignWhatsAppJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Page;
use App\Services\Campaigns\QueueDepthProbe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class DispatchScheduledCampaignMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_jobs_for_eligible_pending_rows(): void
    {
        Queue::fake();
        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => true]);
        $campaign = Campaign::factory()->create([
            'status'         => 'active',
            'platform'       => 'whatsapp',
            'sender_page_id' => $page->id,
        ]);
        foreach (range(1, 3) as $i) {
            CampaignRecipient::factory()->create([
                'campaign_id'  => $campaign->id,
                'channel'      => 'whatsapp',
                'phone'        => "+2010000000{$i}",
                'email'        => null,
                'status'       => 'pending',
                'scheduled_at' => now()->subMinute(),
            ]);
        }

        $this->artisan('campaigns:dispatch-recipients')->assertSuccessful();

        Queue::assertPushed(SendCampaignWhatsAppJob::class, 3);
    }

    public function test_pauses_campaign_when_sender_page_is_disconnected(): void
    {
        Queue::fake();
        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => false]);
        $campaign = Campaign::factory()->create([
            'status'         => 'active',
            'platform'       => 'whatsapp',
            'sender_page_id' => $page->id,
        ]);
        CampaignRecipient::factory()->create([
            'campaign_id'  => $campaign->id,
            'channel'      => 'whatsapp',
            'phone'        => '+201000000001',
            'email'        => null,
            'status'       => 'pending',
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('campaigns:dispatch-recipients')->assertSuccessful();

        Queue::assertNothingPushed();
        $fresh = $campaign->fresh();
        $this->assertSame('paused', $fresh->status);
        $this->assertSame('sender page unavailable', $fresh->paused_reason);
    }

    public function test_backpressure_skips_dispatch_when_queue_depth_exceeds_threshold(): void
    {
        Queue::fake();
        config(['campaigns.backpressure_threshold' => 5]);

        $probe = Mockery::mock(QueueDepthProbe::class);
        $probe->shouldReceive('depthFor')->with('campaigns')->andReturn(999);
        $this->app->instance(QueueDepthProbe::class, $probe);

        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => true]);
        $campaign = Campaign::factory()->create([
            'status'         => 'active',
            'platform'       => 'whatsapp',
            'sender_page_id' => $page->id,
        ]);
        CampaignRecipient::factory()->create([
            'campaign_id'  => $campaign->id,
            'channel'      => 'whatsapp',
            'phone'        => '+201000000001',
            'email'        => null,
            'status'       => 'pending',
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('campaigns:dispatch-recipients')->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertSame('active', $campaign->fresh()->status); // NOT paused — just skipped
    }
}
