<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendCampaignEmailJob;
use App\Jobs\SendCampaignWhatsAppJob;
use App\Models\Campaign;
use App\Services\Campaigns\CampaignScheduler;
use App\Services\Campaigns\QueueDepthProbe;
use Illuminate\Console\Command;

class DispatchScheduledCampaignMessages extends Command
{
    // Renamed from campaigns:dispatch-scheduled to avoid collision with
    // App\Console\Commands\DispatchScheduledCampaigns (whole-campaign scheduler
    // that flips scheduled → active). This one operates one level down: per
    // recipient_row scheduled_at within an already-active campaign.
    protected $signature   = 'campaigns:dispatch-recipients';
    protected $description = 'Dispatch due campaign recipient rows onto the campaigns queue (gated).';

    public function handle(CampaignScheduler $scheduler, QueueDepthProbe $probe): int
    {
        $depth = $probe->depthFor('campaigns');
        $threshold = (int) config('campaigns.backpressure_threshold', 500);
        if ($depth >= $threshold) {
            $this->warn("Backpressure: campaigns queue depth {$depth} >= {$threshold}. Skipping tick.");

            return self::SUCCESS;
        }

        // Check page circuit-breaker: pause campaigns with disconnected sender pages BEFORE claiming.
        $pausedCount = 0;
        foreach (Campaign::query()->where('status', 'active')->with('senderPage')->get() as $c) {
            if (! $c->senderPage || ! $c->senderPage->is_active) {
                $c->update(['status' => 'paused', 'paused_reason' => 'sender page unavailable']);
                $this->warn("Paused campaign #{$c->id}: sender page unavailable.");
                $pausedCount++;
            }
        }

        $ceiling = (int) config('campaigns.dispatch_ceiling_per_tick', 50);
        $batch = $scheduler->claimNextBatch($ceiling);

        foreach ($batch as $r) {
            // Re-check campaign status in case it was just paused above.
            $campaign = $r->campaign ?? $r->load('campaign')->campaign;
            if ($campaign->status !== 'active') {
                continue; // Skip if campaign is no longer active
            }

            match ($r->channel) {
                'whatsapp' => SendCampaignWhatsAppJob::dispatch($r->id),
                'email'    => class_exists(SendCampaignEmailJob::class)
                                ? SendCampaignEmailJob::dispatch($r->id)
                                : $this->warn("SendCampaignEmailJob missing; skipping email recipient #{$r->id}."),
                default    => $this->error("Unknown channel for recipient #{$r->id}: {$r->channel}"),
            };
        }

        $this->info("Dispatched {$batch->count()} campaign message(s).");

        return self::SUCCESS;
    }
}
