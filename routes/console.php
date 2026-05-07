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
