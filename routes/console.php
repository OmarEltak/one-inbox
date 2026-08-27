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
