<?php

use App\Console\Commands\SyncGoogleCalendarChangesCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('google-calendar:sync-run', function () {
    $this->call(SyncGoogleCalendarChangesCommand::class);
})->purpose('Dispatch Google Calendar sync jobs for connected users');

use App\Services\Integrations\GoogleCalendarWatchService;
use Illuminate\Support\Facades\Schedule;

// Renew expiring Google Calendar push notification watches (daily check)
Schedule::call(fn () => app(GoogleCalendarWatchService::class)->renewExpiringWatches())
    ->hourly()
    ->name('google-calendar-watch-renew')
    ->withoutOverlapping();
