<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('users:send-verification-reminders')->daily();
Schedule::command('users:purge-unverified')->daily();

/*
 * Kolejka (analytics, Sendy, e-mail) — NIE przez schedule:run na produkcji.
 *
 * Prod SeoHost: osobny cron co minutę z flock:
 *   queue:work database --queue=default,analytics --stop-when-empty --max-time=55 ...
 * (musi obejmować kolejkę analytics — StoreAnalyticsEventJob).
 *
 * schedule:run na prod tylko dla komend daily() powyżej.
 * Zob. pneadm/docs/deploy/PRODUCTION_QUEUE_OPS.md (sekcja „Cron pnedu.pl”).
 *
 * Lokalnie (Sail): sail artisan queue:work database --queue=default,analytics
 */
