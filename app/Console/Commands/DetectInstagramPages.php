<?php

namespace App\Console\Commands;

use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Services\Platforms\FacebookPlatform;
use Illuminate\Console\Command;

class DetectInstagramPages extends Command
{
    protected $signature = 'inbox:detect-instagram';
    protected $description = 'Re-run Instagram account detection for all connected Facebook pages';

    public function handle(FacebookPlatform $platform): int
    {
        $fbPages = Page::where('platform', 'facebook')
            ->where('is_active', true)
            ->with('connectedAccount')
            ->get();

        if ($fbPages->isEmpty()) {
            $this->error('No active Facebook pages found.');
            return 1;
        }

        $found = 0;

        foreach ($fbPages as $page) {
            $this->line("Checking: {$page->name}");

            $igPage = $platform->detectInstagramAccount($page, $page->connectedAccount);

            if ($igPage) {
                $this->info("  → Found Instagram: {$igPage->name}");
                $found++;
            } else {
                $this->warn("  → No Instagram account linked (or missing permission)");
            }
        }

        $this->newLine();
        $this->info("Done. Found {$found} Instagram account(s).");

        return 0;
    }
}
