<?php

namespace App\Jobs;

use App\Console\Commands\FetchEmails;
use App\Models\Page;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchEmailsForPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300; // IMAP body fetches can take several minutes

    public function __construct(public int $pageId) {}

    public function handle(): void
    {
        $page = Page::find($this->pageId);

        if (! $page || ! $page->is_active) {
            return;
        }

        try {
            (new FetchEmails)->fetchForPage($page);
        } catch (\Throwable $e) {
            Log::error("FetchEmailsForPageJob: failed for page {$this->pageId}", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
