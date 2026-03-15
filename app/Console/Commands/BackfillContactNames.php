<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\ContactPlatform;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BackfillContactNames extends Command
{
    protected $signature = 'inbox:backfill-contact-names
                            {--team= : Only backfill for a specific team ID}
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Re-fetch participant names from Meta conversations API for contacts missing names';

    public function handle(): int
    {
        $version = config('services.meta.graph_api_version', 'v21.0');
        $isDryRun = $this->option('dry-run');

        $pageQuery = Page::where('is_active', true)
            ->whereIn('platform', ['facebook', 'instagram'])
            ->whereNotNull('page_access_token');

        if ($teamId = $this->option('team')) {
            $pageQuery->where('team_id', $teamId);
        }

        $pages = $pageQuery->get();

        if ($pages->isEmpty()) {
            $this->warn('No active Facebook/Instagram pages found.');
            return self::FAILURE;
        }

        $totalUpdated = 0;

        foreach ($pages as $page) {
            $this->line("\nPage: [{$page->id}] {$page->name} ({$page->platform})");

            $metaPlatform = $page->platform === 'instagram' ? 'instagram' : 'messenger';
            $nextUrl = null;

            do {
                $response = $nextUrl
                    ? Http::withToken($page->page_access_token)->get($nextUrl)
                    : Http::withToken($page->page_access_token)
                        ->get("https://graph.facebook.com/{$version}/{$page->platform_page_id}/conversations", [
                            'fields' => 'id,participants',
                            'platform' => $metaPlatform,
                            'limit' => 50,
                        ]);

                if ($response->failed()) {
                    $this->warn("  API error {$response->status()}: " . $response->body());
                    break;
                }

                foreach ($response->json('data', []) as $convData) {
                    $participant = collect($convData['participants']['data'] ?? [])
                        ->first(fn ($p) => $p['id'] !== $page->platform_page_id);

                    if (! $participant || empty($participant['name'])) {
                        continue;
                    }

                    $participantId = $participant['id'];
                    $participantName = $participant['name'];

                    // Find the ContactPlatform entry for this participant
                    $cp = ContactPlatform::where('platform', $page->platform)
                        ->where('platform_contact_id', $participantId)
                        ->first();

                    if (! $cp) {
                        continue;
                    }

                    $contact = $cp->contact;

                    if (! $contact || ! empty($contact->name)) {
                        continue; // Already has a name, skip
                    }

                    $this->line("  [" . ($isDryRun ? 'DRY' : 'OK') . "] Contact #{$contact->id} ({$participantId}) → \"{$participantName}\"");

                    if (! $isDryRun) {
                        $contact->update(['name' => $participantName]);
                        $cp->update(['platform_name' => $participantName]);
                    }

                    $totalUpdated++;
                }

                $nextUrl = $response->json('paging.next');

            } while ($nextUrl);
        }

        $this->newLine();
        $this->info("Done. " . ($isDryRun ? "Would update" : "Updated") . ": {$totalUpdated} contact(s).");

        return self::SUCCESS;
    }
}
