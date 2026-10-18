<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\AiProviderInterface;
use App\Models\Contact;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Benchmarks `AiProviderInterface::scoreMessage()` end-to-end latency using
 * unpersisted Message + Contact instances. No DB writes, no outbound sends,
 * no queue dispatch. Pure NaraRouter → provider → response round-trip.
 *
 * Each call is a real HTTP request to NaraRouter and burns ~1 scoring-model
 * call ($0.0004 or so). Default --count=20 → ~$0.01 per run.
 *
 * Run (prod):
 *   php artisan load-test:ai-latency --count=20
 *   php artisan load-test:ai-latency --count=20 --delay-ms=0
 *
 * For concurrent-call throughput, run multiple of these in parallel SSH shells.
 */
class LoadTestAiLatency extends Command
{
    protected $signature = 'load-test:ai-latency
        {--count=20 : Number of AI calls to make sequentially}
        {--delay-ms=0 : Milliseconds to sleep between calls (0 = back-to-back)}';

    protected $description = 'Measure direct NaraRouter scoreMessage() latency with no DB/queue overhead.';

    private const SAMPLE_MESSAGES = [
        'Hey do you have pricing info for 50 users?',
        'What is your availability next week?',
        'Can I get a discount on the annual plan?',
        'My email is sales@example.com, send details',
        'I would like to book a demo call',
        'How does this compare to Intercom?',
        'Not interested, please remove me',
        'Thats too expensive for our budget',
        'Just wondering what you do',
        'Hi there',
    ];

    public function handle(AiProviderInterface $ai): int
    {
        $count   = (int) $this->option('count');
        $delayMs = (int) $this->option('delay-ms');

        if ($count < 1 || $count > 500) {
            $this->error('count must be between 1 and 500');

            return self::FAILURE;
        }

        $this->line("AI provider class: " . get_class($ai));
        $this->line("NaraRouter cooldown active? " . (Cache::has('nararouter:cooldown_until') ? 'YES' : 'no'));
        $this->line("Running {$count} scoreMessage() calls sequentially...");
        $this->newLine();

        // Unpersisted model instances — AI provider only reads properties.
        $message = new Message([
            'content'    => 'placeholder',
            'direction'  => 'inbound',
            'sender_type' => 'contact',
        ]);
        $contact = new Contact([
            'name'        => 'Load Test Contact',
            'lead_score'  => 10,
            'lead_status' => 'new',
        ]);

        $latencies = [];
        $errors    = 0;
        $totalStart = microtime(true);

        for ($i = 1; $i <= $count; $i++) {
            $message->content = self::SAMPLE_MESSAGES[($i - 1) % count(self::SAMPLE_MESSAGES)];
            $start = microtime(true);

            try {
                $events = $ai->scoreMessage($message, $contact);
                $elapsed = microtime(true) - $start;
                $latencies[] = $elapsed;
                $this->line(sprintf(
                    '  %3d: %6.2fs  events=%d',
                    $i,
                    $elapsed,
                    is_array($events) ? count($events) : -1
                ));
            } catch (\Throwable $e) {
                $elapsed = microtime(true) - $start;
                $errors++;
                $this->warn(sprintf(
                    '  %3d: %6.2fs  ERROR: %s',
                    $i,
                    $elapsed,
                    substr($e->getMessage(), 0, 80)
                ));
            }

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        $totalElapsed = microtime(true) - $totalStart;

        $this->newLine();
        if (empty($latencies)) {
            $this->error('No successful calls.');

            return self::FAILURE;
        }

        sort($latencies);
        $n = count($latencies);
        $p = fn ($pct) => $latencies[(int) floor($n * $pct)] ?? $latencies[$n - 1];

        $this->info(sprintf(
            'Total: %d calls, %d errors, %.1fs wall time, %.2f calls/sec effective',
            $count,
            $errors,
            $totalElapsed,
            $count / max($totalElapsed, 0.001)
        ));
        $this->line(sprintf('  min:  %6.2fs', min($latencies)));
        $this->line(sprintf('  p50:  %6.2fs', $p(0.50)));
        $this->line(sprintf('  p95:  %6.2fs', $p(0.95)));
        $this->line(sprintf('  p99:  %6.2fs', $p(0.99)));
        $this->line(sprintf('  max:  %6.2fs', max($latencies)));
        $this->line(sprintf('  avg:  %6.2fs', array_sum($latencies) / $n));

        $this->newLine();
        $this->line("NaraRouter cooldown active after run? " . (Cache::has('nararouter:cooldown_until') ? 'YES' : 'no'));

        return self::SUCCESS;
    }
}
