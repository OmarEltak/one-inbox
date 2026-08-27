<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * Direct in-process queue-depth measurement.
 *
 * The scheduler MUST measure queue depth via this probe — never via HTTP to
 * /health/metrics. Coupling the scheduler to its own HTTP surface would create
 * a circular dependency that fails hardest under load.
 */
class QueueDepthProbe
{
    public function depthFor(string $queue): int
    {
        if (config('queue.default') === 'database') {
            return (int) DB::table(config('queue.connections.database.table', 'jobs'))
                ->where('queue', $queue)
                ->count();
        }

        return (int) Queue::size($queue);
    }
}
