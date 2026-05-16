<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendCampaignEmailJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Console\Command;

/**
 * Pick due email-campaign recipients and dispatch send jobs.
 * Runs every minute; processes up to BATCH per tick to keep
 * latency bounded.
 */
class DispatchScheduledCampaignEmails extends Command
{
    protected $signature = 'campaigns:dispatch-emails {--batch=50}';

    protected $description = 'Dispatch pending email-campaign recipient jobs that are due.';

    public function handle(): int
    {
        $batch = max(1, (int) $this->option('batch'));

        $activeCampaignIds = Campaign::query()
            ->where('status', Campaign::STATUS_ACTIVE)
            ->where('platform', 'email')
            ->pluck('id');

        if ($activeCampaignIds->isEmpty()) {
            return self::SUCCESS;
        }

        $due = CampaignRecipient::query()
            ->whereIn('campaign_id', $activeCampaignIds)
            ->where('status', CampaignRecipient::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            })
            ->orderBy('scheduled_at')
            ->limit($batch)
            ->get();

        foreach ($due as $recipient) {
            SendCampaignEmailJob::dispatch($recipient->id);
        }

        $this->info("Dispatched {$due->count()} email send job(s).");

        return self::SUCCESS;
    }
}
