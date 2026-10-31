<?php

use App\Jobs\AutoComplete;
use App\Jobs\ExpireRequests;
use App\Jobs\PurgeIdDocuments;
use App\Jobs\ReconcilePayments;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Booking lifecycle (ADR 0004). Locally: `php artisan schedule:work`.
// In production cron runs `php artisan schedule:run` every minute.
Schedule::job(new ExpireRequests)->everyMinute();
Schedule::job(new AutoComplete)->everyTenMinutes();
Schedule::job(new ReconcilePayments)->everyFifteenMinutes();
Schedule::job(new PurgeIdDocuments)->dailyAt('03:30');
