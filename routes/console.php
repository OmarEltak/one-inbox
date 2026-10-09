<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('emails:fetch')->everyTwoMinutes();
Schedule::command('campaigns:dispatch-emails')->everyMinute()->withoutOverlapping();
// Whole-campaign scheduler (flips scheduled → active when scheduled_at arrives).
Schedule::command('campaigns:dispatch-scheduled')->everyMinute()->withoutOverlapping();
// Per-recipient scheduler (dispatches due campaign_recipient rows onto the
// campaigns queue; enforces backpressure + page circuit-breaker).
Schedule::command('campaigns:dispatch-recipients')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
Schedule::command('snapchat:fetch-messages')->everyTwoMinutes();
Schedule::command('instagram:refresh-subscriptions')->monthly();

// Hourly subscription-health check for Facebook/Instagram pages.
// Writes metadata.subscription_error on pages whose token is invalid or
// which are no longer subscribed to our app on Meta's side. The UI
// surfaces this with a warning banner + reconnect CTA. Catches the
// "silent OAuth re-run dropped a page" failure mode (Brandk, 2026-10-01).
Schedule::command('pages:health-check')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Phase C — onboarding nudge sweep. Runs once a day; the job itself is
// idempotent per (team, stage) so a duplicate run within 24h is a no-op.
Schedule::job(new \App\Jobs\SendOnboardingNudge())
    ->dailyAt('09:00')
    ->name('onboarding-nudges')
    ->withoutOverlapping();

// Phase D — trial lifecycle. Runs once per day at 09:00 UTC. Walks all teams
// on the trial state machine and transitions / emails as appropriate.
// Idempotent: safe to run twice on the same day (per-team+event cache guard).
Schedule::job(new \App\Jobs\TrialExpiryCheck())
    ->dailyAt('09:00')
    ->name('trial-expiry-check')
    ->withoutOverlapping();

// Phase A — monthly AI credit allowance reset. Runs daily at 02:00 UTC.
// For each team whose billing_cycle_anchor day matches today, zeros the
// unused monthly remainder and grants a fresh plan quota. Idempotent per
// team per UTC day (see ResetMonthlyAiCredits::alreadyGrantedToday).
Schedule::command('credits:reset-monthly')
    ->dailyAt('02:00')
    ->withoutOverlapping();

// Proactive capacity monitor (docs/OT1_LIMITS.md §7). Runs every 5 min,
// emails Omar when any signal trips a threshold. Per-signal 60-min
// cool-down inside the command itself so we don't spam during a
// sustained incident.
Schedule::command('capacity:health-check')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Self-healing NaraRouter chain (2026-10-09). Hits /v1/models nightly,
// filters to free (zero per-token price), categorizes by capability
// (text/vision/reasoning), writes the pool to Redis for NaraRouterProvider
// to read. Prevents the 'dead model in chain' cascade failure that burned
// a 30-min global cooldown on 2026-10-08. See App\Services\Ai\NaraRouterPool.
// No model name is hardcoded in app code — chain members come from this API
// response, or an explicit operator-set NARAROUTER_TEXT_MODELS / _VISION_MODELS.
Schedule::command('nararouter:refresh-chain')
    ->dailyAt('03:00')
    ->withoutOverlapping();
