<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessCampaign;
use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Fires scheduled campaigns whose scheduled_at has arrived.
 *
 * Campaigns created via /campaigns with "Schedule for later" enter status=scheduled
 * with a scheduled_at timestamp. This command (run every minute by the Laravel
 * scheduler) picks them up as their time reaches now, flips them to status=active,
 * and dispatches ProcessCampaign so the send loop starts.
 *
 * Not to be confused with DispatchScheduledCampaignEmails, which dispatches per-
 * recipient email send jobs for an ALREADY-active email campaign. That command
 * is the send-loop for email; this command is the campaign starter for every
 * non-email platform (facebook, instagram, telegram, whatsapp).
 */
class DispatchScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:dispatch-scheduled {--batch=25}';

    protected $description = 'Flip scheduled campaigns whose time has arrived to active, and dispatch ProcessCampaign for each.';

    public function handle(): int
    {
        $batch = max(1, (int) $this->option('batch'));

        $due = Campaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            // Email campaigns have their own recipient-level scheduling loop
            // (DispatchScheduledCampaignEmails). Everything else fires via
            // ProcessCampaign, which iterates conversations directly.
            ->where('platform', '!=', 'email')
            ->orderBy('scheduled_at')
            ->limit($batch)
            ->get();

        if ($due->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($due as $campaign) {
            try {
                $campaign->update(['status' => 'active']);
                ProcessCampaign::dispatch($campaign->id);
                Log::info('Scheduled campaign flipped to active', [
                    'campaign_id'  => $campaign->id,
                    'platform'     => $campaign->platform,
                    'scheduled_at' => $campaign->scheduled_at?->toIso8601String(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch scheduled campaign', [
                    'campaign_id' => $campaign->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $this->info("Dispatched {$due->count()} scheduled campaign(s).");

        return self::SUCCESS;
    }
}
