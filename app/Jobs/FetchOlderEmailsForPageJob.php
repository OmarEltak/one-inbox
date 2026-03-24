<?php

namespace App\Jobs;

use App\Console\Commands\FetchEmails;
use App\Models\Page;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchOlderEmailsForPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300;

    public function __construct(public int $pageId) {}

    public function handle(): void
    {
        $page = Page::find($this->pageId);

        if (! $page || ! $page->is_active) {
            return;
        }

        $meta            = $page->metadata ?? [];
        $oldestFetchedAt = $meta['oldest_fetched_at'] ?? null;

        if (! $oldestFetchedAt) {
            // No anchor date yet — nothing to paginate back from
            $page->update(['metadata' => array_merge($meta, ['has_more_imap' => false])]);
            return;
        }

        $cutoff   = Carbon::createFromTimestamp($oldestFetchedAt)->startOfDay();
        $email    = $page->platform_page_id;
        $password = decrypt($page->page_access_token);

        $command = new FetchEmails;
        $client  = $command->buildClient($email, $password, $meta);
        $client->connect();

        try {
            $folder = $client->getFolder('INBOX');

            if (! $folder) {
                Log::warning("FetchOlderEmailsForPageJob: could not open INBOX for {$email}");
                return;
            }

            // Step 1: headers only (lightweight) for everything before the cutoff date
            $headers = $folder->query()
                ->before($cutoff)
                ->setFetchBody(false)
                ->leaveUnread()
                ->get();

            if ($headers->isEmpty()) {
                $page->update(['metadata' => array_merge($meta, ['has_more_imap' => false])]);
                return;
            }

            // Take the 50 most recent (highest UIDs) from before the cutoff
            $top50      = $headers->sortByDesc(fn ($m) => (int) $m->uid)->take(50);
            $oldestDate = Carbon::parse($top50->last()->date->first()->timestamp)->startOfDay();
            $newestDate = Carbon::parse($top50->first()->date->first()->timestamp)->addDay();

            // Step 2: fetch with bodies using the date window of those 50 messages
            $batch = $folder->query()
                ->since($oldestDate)
                ->before(min($newestDate, $cutoff))
                ->setFetchBody(true)
                ->leaveUnread()
                ->get();

            if ($batch->isEmpty()) {
                $page->update(['metadata' => array_merge($meta, ['has_more_imap' => false])]);
                return;
            }

            // Process and find the oldest date in this batch
            $minTimestamp = PHP_INT_MAX;

            foreach ($batch as $message) {
                try {
                    $command->processMessage($message, $page, $email);
                    $msgTs = $message->date->first()?->timestamp ?? time();
                    if ($msgTs < $minTimestamp) {
                        $minTimestamp = $msgTs;
                    }
                } catch (\Throwable $e) {
                    Log::warning("FetchOlderEmailsForPageJob: failed to process message for {$email}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $updatedMeta                      = $meta;
            $updatedMeta['oldest_fetched_at'] = $minTimestamp;
            // Still has more if oldest date is further back than our 30-day initial window
            $updatedMeta['has_more_imap']     = $minTimestamp < Carbon::now()->subYears(10)->timestamp
                ? false
                : $headers->count() >= 50; // if we got fewer than 50, likely exhausted
            $page->update(['metadata' => $updatedMeta]);

        } finally {
            $client->disconnect();
        }
    }
}
