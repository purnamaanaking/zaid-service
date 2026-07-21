<?php

namespace App\Console\Commands;

use App\Jobs\Calendar\SyncGoogleCalendarConnectionJob;
use App\Models\UserCalendarConnection;
use Illuminate\Console\Command;

class SyncGoogleCalendarChangesCommand extends Command
{
    protected $signature = 'google-calendar:sync';

    protected $description = 'Sync Google Calendar changes into local events';

    public function handle(): int
    {
        UserCalendarConnection::query()
            ->where('provider', 'google_calendar')
            ->where('status', 'connected')
            ->each(fn (UserCalendarConnection $connection) => SyncGoogleCalendarConnectionJob::dispatch($connection->id));

        $this->info('Google Calendar sync jobs dispatched.');

        return self::SUCCESS;
    }
}
