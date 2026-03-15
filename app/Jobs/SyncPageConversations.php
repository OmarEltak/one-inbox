<?php

namespace App\Jobs;

use App\Models\Page;
use App\Services\Platforms\FacebookPlatform;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPageConversations implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;
    public int $tries = 2;

    public function __construct(public Page $page) {}

    public function handle(): void
    {
        $platform = match ($this->page->platform) {
            'facebook', 'instagram' => app(FacebookPlatform::class),
            default => null,
        };

        if ($platform) {
            $platform->fetchConversations($this->page);
        }
    }
}
