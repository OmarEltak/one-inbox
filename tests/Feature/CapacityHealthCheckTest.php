<?php

declare(strict_types=1);

use App\Mail\CapacityAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * capacity:health-check — proactive monitor per docs/OT1_LIMITS.md §7.
 *
 * These tests exercise the cool-down + email dispatch logic. System-metric
 * gathering (CPU/RAM/disk) is best-effort and skipped in test env (no
 * /proc on macOS/Windows CI runners).
 */

beforeEach(function () {
    Mail::fake();
    Cache::flush();
});

it('runs without crashing when no signals are tripped', function () {
    // Ensure queue depths are 0 (Redis is real via cache in test env).
    $this->artisan('capacity:health-check')->assertOk();
    Mail::assertNothingSent();
});

it('sends alert email when a signal is tripped and honors 60-min cool-down', function () {
    // Trip the NaraRouter global-cooldown signal — always trips when the cache
    // key is set, independent of Redis LLEN availability in test env.
    Cache::put('nararouter:cooldown_until', now()->addMinutes(30)->toIso8601String(), 60);

    // First run — should send.
    $this->artisan('capacity:health-check')->assertOk();
    Mail::assertSent(CapacityAlert::class, 1);

    // Second run within 60 min — cool-down blocks the resend.
    $this->artisan('capacity:health-check')->assertOk();
    Mail::assertSent(CapacityAlert::class, 1);

    // --force bypasses the cool-down.
    $this->artisan('capacity:health-check --force')->assertOk();
    Mail::assertSent(CapacityAlert::class, 2);
});

it('skips gracefully when recipient is not configured', function () {
    config()->set('services.capacity_alerts.to', '');

    $this->artisan('capacity:health-check')->assertOk();
    Mail::assertNothingSent();
});

it('subject contains the tripped signal message', function () {
    Cache::put('nararouter:cooldown_until', now()->addMinutes(30)->toIso8601String(), 60);

    $this->artisan('capacity:health-check')->assertOk();
    Mail::assertSent(CapacityAlert::class, function ($mail) {
        return str_starts_with($mail->envelope()->subject, '[OT1] NaraRouter global cooldown');
    });
});
