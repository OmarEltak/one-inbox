<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ai\NaraRouterPool;
use Illuminate\Console\Command;

/**
 * `php artisan nararouter:refresh-chain`
 *
 * Hits NaraRouter's /v1/models endpoint, filters to free models (zero per-token
 * price), categorizes them by capability (text/vision/reasoning) and writes the
 * resulting pool to Redis for NaraRouterProvider to read on the next request.
 *
 * Run manually to recover from a sudden model-list change; scheduled nightly in
 * routes/console.php so the pool stays current without operator intervention.
 */
final class NaraRouterRefreshChain extends Command
{
    protected $signature = 'nararouter:refresh-chain {--show : Print the resulting chains after refresh}';

    protected $description = 'Fetch and cache the free-model pool from NaraRouter /v1/models';

    public function handle(): int
    {
        $pool = new NaraRouterPool(
            baseUrl: (string) config('services.nararouter.base_url'),
            apiKey: (string) config('services.nararouter.api_key'),
            alertEmail: config('services.nararouter.alert_email'),
        );

        $this->info('Refreshing NaraRouter model pool…');
        $ok = $pool->refresh();

        if (! $ok) {
            $this->error('Refresh failed: ' . ($pool->currentLastError() ?? 'unknown'));
            return self::FAILURE;
        }

        $status = $pool->status();
        $this->info("Refreshed at {$status['refreshed_at']} — pool has {$status['pool_size']} free models.");

        if ($this->option('show')) {
            $this->newLine();
            $this->line('<fg=cyan>Text chain:</>      ' . (empty($status['text_chain'])      ? '(empty)' : implode(', ', $status['text_chain'])));
            $this->line('<fg=cyan>Vision chain:</>    ' . (empty($status['vision_chain'])    ? '(empty)' : implode(', ', $status['vision_chain'])));
            $this->line('<fg=cyan>Reasoning chain:</> ' . (empty($status['reasoning_chain']) ? '(empty)' : implode(', ', $status['reasoning_chain'])));

            if (empty($status['vision_chain'])) {
                $this->newLine();
                $this->warn('Vision chain is EMPTY — vision tasks will have no fallback.');
                $this->warn('An alert has been emailed to the operator (if configured).');
            }
        }

        return self::SUCCESS;
    }
}
