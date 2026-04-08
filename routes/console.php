<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('emails:fetch')->everyTwoMinutes();
Schedule::command('snapchat:fetch-messages')->everyTwoMinutes();
Schedule::command('instagram:refresh-subscriptions')->monthly();
