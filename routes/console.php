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
 * Kolejka zadań (np. zapis do Sendy po rejestracji zaświadczenia) – na hostingu
 * współdzielonym bez Supervisora: cron co minutę wywołuje schedule:run.
 * Zob. pneadm/docs/QUEUE_SEOHOST.md (ten sam schemat dla domeny pnedu.pl).
 */
Schedule::command('queue:work --stop-when-empty --max-time=300')
    ->everyMinute()
    ->withoutOverlapping(5);
