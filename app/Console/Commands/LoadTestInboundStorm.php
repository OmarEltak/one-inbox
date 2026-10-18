<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessIncomingMessage;
use App\Models\WebhookLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Dispatches synthetic Meta-webhook jobs into the `urgent` queue to measure
 * inbound-pipeline throughput on prod.
 *
 * Fake payloads use `entry.id = "LOADTEST_SINK_*"` so no live Page matches and
 * `ProcessIncomingMessage::handleMetaMessage` returns without AI calls or
 * outbound sends. Only the WebhookLog insert + queue LPUSH + job dequeue +
 * page-lookup SELECT are exercised — the pure "how fast can we ingest and
 * route" number.
 *
 * Run (prod):
 *   php artisan load-test:inbound-storm --count=500 --rate=20
 *   php artisan load-test:inbound-storm --cleanup
 */
class LoadTestInboundStorm extends Command
{
    protected $signature = 'load-test:inbound-storm
        {--count=100 : Number of synthetic webhook jobs to enqueue}
        {--rate=0 : If >0, cap enqueue rate to this many jobs/sec (0 = as fast as possible)}
        {--watch-seconds=120 : Max seconds to watch urgent-queue drain after enqueue}
        {--cleanup : Delete all load-test rows and exit}';

    protected $description = 'Flood the urgent queue with synthetic Meta webhooks to measure inbound throughput.';

    private const SINK_PAGE_PREFIX = 'LOADTEST_SINK_';

    public function handle(): int
    {
        if ($this->option('cleanup')) {
            return $this->cleanup();
        }

        $count = (int) $this->option('count');
        $rate  = (int) $this->option('rate');
        $watch = (int) $this->option('watch-seconds');

        if ($count < 1 || $count > 10_000) {
            $this->error('count must be between 1 and 10000');

            return self::FAILURE;
        }

        $this->info("Enqueuing {$count} synthetic inbound webhooks"
            . ($rate > 0 ? " at ~{$rate}/sec" : ' as fast as possible'));

        $startDepth = (int) Redis::llen('queues:urgent');
        $this->line("urgent queue depth at start: {$startDepth}");

        $enqueueStart = microtime(true);
        $batchStartTs = microtime(true);
        $batchCount = 0;

        for ($i = 1; $i <= $count; $i++) {
            $pageId   = self::SINK_PAGE_PREFIX . $i;
            $senderId = 'loadtest_sender_' . $i;
            $payload  = [
                'object' => 'page',
                'entry'  => [[
                    'id'        => $pageId,
                    'time'      => (int) (microtime(true) * 1000),
                    'messaging' => [[
                        'sender'    => ['id' => $senderId],
                        'recipient' => ['id' => $pageId],
                        'timestamp' => (int) (microtime(true) * 1000),
                        'message'   => [
                            'mid'  => 'loadtest_mid_' . bin2hex(random_bytes(8)),
                            'text' => 'load-test payload ' . $i,
                        ],
                    ]],
                ]],
            ];

            $log = WebhookLog::create([
                'team_id'    => null,
                'platform'   => 'facebook',
                'event_type' => 'load-test.inbound',
                'payload'    => $payload,
            ]);

            ProcessIncomingMessage::dispatch($log->id);

            $batchCount++;

            if ($rate > 0 && $batchCount >= $rate) {
                $elapsed = microtime(true) - $batchStartTs;
                if ($elapsed < 1.0) {
                    usleep((int) ((1.0 - $elapsed) * 1_000_000));
                }
                $batchStartTs = microtime(true);
                $batchCount = 0;
            }

            if ($i % 50 === 0) {
                $this->line("  enqueued {$i} / {$count} (urgent depth now " . Redis::llen('queues:urgent') . ')');
            }
        }

        $enqueueElapsed = microtime(true) - $enqueueStart;
        $enqueueRate = $count / max($enqueueElapsed, 0.001);
        $peakDepth = (int) Redis::llen('queues:urgent');

        $this->newLine();
        $this->info(sprintf(
            'Enqueued %d jobs in %.2fs (%.1f/sec effective). urgent depth now: %d (was %d)',
            $count,
            $enqueueElapsed,
            $enqueueRate,
            $peakDepth,
            $startDepth,
        ));

        $this->line('Watching queue drain for up to ' . $watch . 's...');
        $watchStart = microtime(true);
        $lastDepth = $peakDepth;
        $drainedAt = null;

        while ((microtime(true) - $watchStart) < $watch) {
            sleep(2);
            $depth = (int) Redis::llen('queues:urgent');
            $elapsed = microtime(true) - $watchStart;
            $this->line(sprintf('  t=%5.1fs  depth=%d', $elapsed, $depth));

            if ($depth <= $startDepth) {
                $drainedAt = $elapsed;
                break;
            }
            $lastDepth = $depth;
        }

        $this->newLine();
        if ($drainedAt !== null) {
            $drainRate = ($peakDepth - $startDepth) / max($drainedAt, 0.001);
            $this->info(sprintf(
                'Queue fully drained in %.1fs after enqueue (drain rate ≈ %.1f jobs/sec).',
                $drainedAt,
                $drainRate,
            ));
        } else {
            $this->warn(sprintf(
                'Queue did NOT drain within %ds — remaining depth: %d. Drain rate ≈ %.1f jobs/sec.',
                $watch,
                $lastDepth,
                ($peakDepth - $lastDepth) / max($watch, 1),
            ));
        }

        $this->newLine();
        $this->line("Processed webhook_logs rows: " . WebhookLog::where('event_type', 'load-test.inbound')
            ->where('processed', true)->count());
        $this->line("Pending webhook_logs rows:   " . WebhookLog::where('event_type', 'load-test.inbound')
            ->where('processed', false)->count());

        return self::SUCCESS;
    }

    private function cleanup(): int
    {
        $this->warn('Deleting all load-test webhook_logs rows...');

        $deleted = DB::table('webhook_logs')
            ->where('event_type', 'load-test.inbound')
            ->delete();

        $this->info("Deleted {$deleted} rows.");

        return self::SUCCESS;
    }
}
