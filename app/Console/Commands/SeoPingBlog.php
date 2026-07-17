<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\PingSearchEnginesJob;
use App\Models\Post;
use Illuminate\Console\Command;

class SeoPingBlog extends Command
{
    protected $signature = 'seo:ping-blog
        {--slug= : Ping a single post by slug (skip to ping all published posts)}
        {--sync : Run synchronously instead of dispatching to the queue}';

    protected $description = 'Notify IndexNow + Google Indexing API about published blog posts.';

    public function handle(): int
    {
        $slug = $this->option('slug');

        $query = Post::published();
        if ($slug) {
            $query->where('slug', $slug);
        }

        $urls = $query->pluck('slug')->map(fn (string $s) => route('blog.show', $s))->all();

        if ($urls === []) {
            $this->warn('No matching published posts.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Pinging %d URL(s)…', count($urls)));

        // IndexNow accepts up to 10,000 URLs per request; batch defensively at 100.
        foreach (array_chunk($urls, 100) as $chunk) {
            $job = new PingSearchEnginesJob($chunk);
            $this->option('sync') ? $job->handle(app(\App\Services\Seo\IndexNowService::class), app(\App\Services\Seo\GoogleIndexingService::class))
                                  : PingSearchEnginesJob::dispatch($chunk);
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
