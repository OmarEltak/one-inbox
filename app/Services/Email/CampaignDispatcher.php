<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use Illuminate\Support\Carbon;

/**
 * Build the per-recipient queue for an email campaign. Spreads sends
 * across days honoring the campaign's daily_cap with jitter between
 * each individual send.
 */
class CampaignDispatcher
{
    /**
     * Insert campaign_recipients rows for every contact matching the
     * campaign's target_criteria. Idempotent — uses unique
     * (campaign_id, email) to skip duplicates.
     *
     * @return int  number of recipients scheduled
     */
    public function schedule(Campaign $campaign): int
    {
        $contacts = $this->resolveContacts($campaign);

        $dailyCap  = max(1, (int) ($campaign->daily_cap ?? 200));
        $jitterMin = max(0, (int) $campaign->jitter_min_seconds);
        $jitterMax = max($jitterMin, (int) $campaign->jitter_max_seconds);

        $now = Carbon::now();
        $cursor = $now->copy();
        $sentToday = 0;
        $dayStart = $now->copy()->startOfDay();
        $scheduled = 0;

        foreach ($contacts as $contact) {
            if (! $contact->email) {
                continue;
            }

            // Day-roll: if we'd exceed the cap, push cursor to start of next day.
            if ($sentToday >= $dailyCap) {
                $dayStart = $dayStart->copy()->addDay();
                $cursor = $dayStart->copy();
                $sentToday = 0;
            }

            $recipient = CampaignRecipient::firstOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'email'       => EmailValidator::normalize($contact->email),
                ],
                [
                    'contact_id'   => $contact->id,
                    'name'         => $contact->name,
                    'custom_fields'=> $contact->metadata,
                    'status'       => CampaignRecipient::STATUS_PENDING,
                    'scheduled_at' => $cursor,
                ]
            );

            if ($recipient->wasRecentlyCreated) {
                $jitter = $jitterMin === $jitterMax
                    ? $jitterMin
                    : random_int($jitterMin, $jitterMax);
                $cursor = $cursor->copy()->addSeconds(max(1, $jitter));
                $sentToday++;
                $scheduled++;
            }
        }

        $campaign->update(['total_contacts' => $campaign->recipients()->count()]);

        return $scheduled;
    }

    /**
     * Resolve the contacts targeted by this campaign. Currently supports
     * the `contact_tag` criterion (set by the email wizard). Future filters
     * can extend this method without changing call sites.
     *
     * @return iterable<Contact>
     */
    private function resolveContacts(Campaign $campaign): iterable
    {
        $criteria = $campaign->target_criteria ?? [];

        $query = Contact::where('team_id', $campaign->team_id)
            ->whereNotNull('email');

        if (! empty($criteria['contact_tag'])) {
            $tag = $criteria['contact_tag'];
            // JSON contains works for both MySQL and SQLite (via JSON1).
            $query->whereJsonContains('tags', $tag);
        }

        if (! empty($criteria['lead_status'])) {
            $query->where('lead_status', $criteria['lead_status']);
        }

        return $query->orderBy('id')->cursor();
    }
}
