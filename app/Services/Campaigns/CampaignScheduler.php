<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CampaignScheduler
{
    public const HARD_DISPATCH_CEILING = 50;

    /**
     * Create campaign_recipients rows with jittered scheduled_at.
     * Phase A: no warmup, no quiet hours (deferred to phase B).
     *
     * @param  array<int, string>  $identifiers  E.164 phones or emails
     */
    public function schedule(Campaign $campaign, array $identifiers, string $channel): void
    {
        $now = Carbon::now();
        $min = max(15, (int) ($campaign->jitter_min_seconds ?? 30));
        $max = max($min, (int) ($campaign->jitter_max_seconds ?? 60));
        $cursor = $now->copy();

        DB::transaction(function () use ($campaign, $identifiers, $channel, $min, $max, &$cursor) {
            foreach ($identifiers as $index => $id) {
                $email = $channel === 'email' ? $id : "placeholder-{$campaign->id}-{$index}@example.com";
                CampaignRecipient::create([
                    'campaign_id'  => $campaign->id,
                    'channel'      => $channel,
                    'phone'        => $channel === 'whatsapp' ? $id : null,
                    'email'        => $email,
                    'status'       => 'pending',
                    'attempts'     => 0,
                    'scheduled_at' => $cursor->copy(),
                ]);
                $cursor->addSeconds(random_int($min, $max));
            }

            $campaign->update(['total_contacts' => count($identifiers)]);
        });
    }

    /**
     * Atomically claim up to $limit pending rows whose scheduled_at is due.
     * Sets status = 'queued' to prevent double-dispatch on the next tick.
     */
    public function claimNextBatch(int $limit): Collection
    {
        $limit = min($limit, self::HARD_DISPATCH_CEILING);

        return DB::transaction(function () use ($limit) {
            $ids = CampaignRecipient::query()
                ->where('status', 'pending')
                ->where('scheduled_at', '<=', now())
                ->orderBy('scheduled_at')
                ->limit($limit)
                ->lockForUpdate()
                ->pluck('id');

            if ($ids->isEmpty()) {
                return collect();
            }

            CampaignRecipient::whereIn('id', $ids)->update(['status' => 'queued']);
            return CampaignRecipient::whereIn('id', $ids)->get();
        });
    }
}
