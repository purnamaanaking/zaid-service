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

use App\Jobs\Calendar\SyncGoogleTasksConnectionJob;
use App\Models\UserCalendarConnection;
use App\Services\Integrations\GoogleCalendarWatchService;
use Illuminate\Support\Facades\Schedule;

// Renew expiring Google Calendar push notification watches
Schedule::call(fn () => app(GoogleCalendarWatchService::class)->renewExpiringWatches())
    ->hourly()
    ->name('google-calendar-watch-renew')
    ->withoutOverlapping();

// Google Tasks inbound sync disabled: it was re-importing old remote tasks after local cleanup.
