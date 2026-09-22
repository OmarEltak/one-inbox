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
