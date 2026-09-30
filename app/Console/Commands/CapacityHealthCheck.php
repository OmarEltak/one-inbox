<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\CapacityAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

/**
 * Proactive capacity monitor — see docs/OT1_LIMITS.md §7.
 *
 * Scheduled every 5 minutes from routes/console.php. Reads system + queue
 * metrics, compares against thresholds, and emails Omar when anything
 * trips. Per-signal 60-min cool-down so we don't spam.
 *
 * Best-effort — designed to never crash the scheduler if a metric source
 * is unavailable (e.g. proc filesystem on non-Linux, Redis outage).
 *
 * Thresholds and destination are env-overridable so we don't need a code
 * ship to tune during an incident.
 */
class CapacityHealthCheck extends Command
{
    protected $signature = 'capacity:health-check {--force : ignore per-signal cool-down and always send}';
    protected $description = 'Check system + queue capacity signals and email Omar on threshold breach';

    /** 60 minutes between repeat alerts for the same signal. */
    protected const COOLDOWN_MINUTES = 60;

    public function handle(): int
    {
        $recipient = (string) config('services.capacity_alerts.to', 'omareltak7@gmail.com');
        if ($recipient === '') {
            $this->warn('capacity_alerts.to not set — skipping.');
            return self::SUCCESS;
        }

        $signals = array_merge(
            $this->systemSignals(),
            $this->queueSignals(),
        );

        $tripped = array_filter($signals, fn ($s) => $s['tripped']);

        if ($tripped === []) {
            $this->info('All capacity signals within thresholds.');
            return self::SUCCESS;
        }

        foreach ($tripped as $key => $signal) {
            if (! $this->option('force') && ! $this->shouldSend($key)) {
                continue;
            }

            try {
                Mail::to($recipient)->send(new CapacityAlert($key, $signal));
                $this->markSent($key);
                $this->line("Alert sent: {$key} — {$signal['message']}");
            } catch (\Throwable $e) {
                Log::warning('Capacity alert email failed', [
                    'signal' => $key,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }

    /** @return array<string, array{tripped: bool, value: mixed, threshold: mixed, message: string}> */
    protected function systemSignals(): array
    {
        $signals = [];

        // CPU 1-minute load average — trip at > 1.8 on 2 vCPU (see doc §7).
        if (file_exists('/proc/loadavg')) {
            $load1 = (float) explode(' ', file_get_contents('/proc/loadavg'))[0];
            $signals['cpu_load'] = [
                'tripped'   => $load1 > (float) config('services.capacity_alerts.cpu_load_max', 1.8),
                'value'     => $load1,
                'threshold' => 1.8,
                'message'   => sprintf('CPU 1-min load avg = %.2f', $load1),
            ];
        }

        // Free RAM — trip when < 500 MB free.
        if (file_exists('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            if (preg_match('/^MemAvailable:\s+(\d+)\s+kB/m', $meminfo, $m)) {
                $freeMb = (int) ($m[1] / 1024);
                $signals['ram_free'] = [
                    'tripped'   => $freeMb < (int) config('services.capacity_alerts.ram_free_min_mb', 500),
                    'value'     => $freeMb,
                    'threshold' => 500,
                    'message'   => sprintf('Free RAM = %d MB', $freeMb),
                ];
            }
        }

        // Disk free — trip when < 5 GB free on / (host of /var/www).
        $freeBytes = @disk_free_space('/var/www') ?: @disk_free_space('/');
        if ($freeBytes !== false) {
            $freeGb = (int) ($freeBytes / 1024 / 1024 / 1024);
            $signals['disk_free'] = [
                'tripped'   => $freeGb < (int) config('services.capacity_alerts.disk_free_min_gb', 5),
                'value'     => $freeGb,
                'threshold' => 5,
                'message'   => sprintf('Disk free on /var/www = %d GB', $freeGb),
            ];
        }

        return $signals;
    }

    /** @return array<string, array{tripped: bool, value: mixed, threshold: mixed, message: string}> */
    protected function queueSignals(): array
    {
        $signals = [];

        // Redis queue-depth checks — best-effort; if Redis is down (test env or
        // real outage) we skip THIS block but still emit non-Redis signals below.
        try {
            $redis = Redis::connection();
            $checks = [
                'urgent'        => (int) config('services.capacity_alerts.queue_urgent_max', 50),
                'transcription' => (int) config('services.capacity_alerts.queue_transcription_max', 20),
                'campaigns'     => (int) config('services.capacity_alerts.queue_campaigns_max', 5000),
                'default'       => (int) config('services.capacity_alerts.queue_default_max', 100),
            ];

            foreach ($checks as $queue => $max) {
                $depth = (int) $redis->llen("queues:{$queue}");
                $signals["queue_{$queue}"] = [
                    'tripped'   => $depth >= $max,
                    'value'     => $depth,
                    'threshold' => $max,
                    'message'   => sprintf('Queue %s depth = %d (threshold %d)', $queue, $depth, $max),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Capacity health-check: Redis queue depths unavailable', ['error' => $e->getMessage()]);
        }

        // NaraRouter global cooldown — see ARCHITECTURE §4. Uses the cache
        // facade directly (not the Redis connection above) so it survives
        // a Redis outage on the LLEN path.
        try {
            $cooldownUntil = Cache::get('nararouter:cooldown_until');
            if ($cooldownUntil !== null) {
                $signals['ai_cooldown'] = [
                    'tripped'   => true,
                    'value'     => $cooldownUntil,
                    'threshold' => null,
                    'message'   => 'NaraRouter global cooldown active — all AI providers exhausted',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Capacity health-check: AI cooldown signal unavailable', ['error' => $e->getMessage()]);
        }

        return $signals;
    }

    protected function shouldSend(string $key): bool
    {
        return ! Cache::has($this->cooldownKey($key));
    }

    protected function markSent(string $key): void
    {
        Cache::put($this->cooldownKey($key), 1, now()->addMinutes(self::COOLDOWN_MINUTES));
    }

    protected function cooldownKey(string $key): string
    {
        return "capacity:alert:cooldown:{$key}";
    }
}
