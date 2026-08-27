<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Jobs\SendCampaignWhatsAppJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Page;
use App\Services\Wuzapi\SendResult;
use App\Services\Wuzapi\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendCampaignWhatsAppJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_paused_campaign_bails_without_calling_sender(): void
    {
        $sender = Mockery::mock(WhatsAppSender::class);
        $sender->shouldNotReceive('send');
        $this->app->instance(WhatsAppSender::class, $sender);

        $campaign = Campaign::factory()->create(['status' => 'paused', 'platform' => 'whatsapp']);
        $r = CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id,
            'channel'     => 'whatsapp',
            'phone'       => '+201026361218',
            'email'       => null,
            'status'      => 'queued',
        ]);

        (new SendCampaignWhatsAppJob($r->id))->handle($sender);

        $this->assertSame('queued', $r->fresh()->status);
    }

    public function test_successful_send_marks_recipient_sent(): void
    {
        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => true]);
        $campaign = Campaign::factory()->create([
            'status'           => 'active',
            'platform'         => 'whatsapp',
            'sender_page_id'   => $page->id,
            'message_template' => 'Hi',
            'sent_count'       => 0,
        ]);
        $r = CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id,
            'channel'     => 'whatsapp',
            'phone'       => '+201026361218',
            'email'       => null,
            'status'      => 'queued',
        ]);

        $sender = Mockery::mock(WhatsAppSender::class);
        $sender->shouldReceive('send')->once()->andReturn(SendResult::ok('wa-1'));
        $this->app->instance(WhatsAppSender::class, $sender);

        (new SendCampaignWhatsAppJob($r->id))->handle($sender);

        $fresh = $r->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertNotNull($fresh->sent_at);
        $this->assertSame(1, $campaign->fresh()->sent_count);
    }

    public function test_transient_failure_increments_attempts_and_reschedules(): void
    {
        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => true]);
        $campaign = Campaign::factory()->create([
            'status'           => 'active',
            'platform'         => 'whatsapp',
            'sender_page_id'   => $page->id,
            'message_template' => 'Hi',
        ]);
        $r = CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id,
            'channel'     => 'whatsapp',
            'phone'       => '+201026361218',
            'email'       => null,
            'status'      => 'queued',
            'attempts'    => 0,
        ]);

        $sender = Mockery::mock(WhatsAppSender::class);
        $sender->shouldReceive('send')->once()->andReturn(SendResult::transient('5xx'));
        $this->app->instance(WhatsAppSender::class, $sender);

        (new SendCampaignWhatsAppJob($r->id))->handle($sender);

        $fresh = $r->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertSame(1, $fresh->attempts);
        $this->assertTrue($fresh->scheduled_at->isFuture());
    }

    public function test_permanent_failure_marks_failed(): void
    {
        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => true]);
        $campaign = Campaign::factory()->create([
            'status'           => 'active',
            'platform'         => 'whatsapp',
            'sender_page_id'   => $page->id,
            'message_template' => 'Hi',
        ]);
        $r = CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id,
            'channel'     => 'whatsapp',
            'phone'       => '+201026361218',
            'email'       => null,
            'status'      => 'queued',
        ]);

        $sender = Mockery::mock(WhatsAppSender::class);
        $sender->shouldReceive('send')->once()->andReturn(SendResult::permanent('invalid number'));
        $this->app->instance(WhatsAppSender::class, $sender);

        (new SendCampaignWhatsAppJob($r->id))->handle($sender);

        $this->assertSame('failed', $r->fresh()->status);
    }

    public function test_disconnected_page_marks_recipient_failed(): void
    {
        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => false]);
        $campaign = Campaign::factory()->create([
            'status'           => 'active',
            'platform'         => 'whatsapp',
            'sender_page_id'   => $page->id,
            'message_template' => 'Hi',
        ]);
        $r = CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id,
            'channel'     => 'whatsapp',
            'phone'       => '+201026361218',
            'email'       => null,
            'status'      => 'queued',
        ]);

        $sender = Mockery::mock(WhatsAppSender::class);
        $sender->shouldNotReceive('send');
        $this->app->instance(WhatsAppSender::class, $sender);

        (new SendCampaignWhatsAppJob($r->id))->handle($sender);

        $this->assertSame('failed', $r->fresh()->status);
    }
}
