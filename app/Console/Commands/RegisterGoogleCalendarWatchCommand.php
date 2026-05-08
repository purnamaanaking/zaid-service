<?php

namespace App\Console\Commands;

use App\Services\Integrations\GoogleCalendarWatchService;
use Illuminate\Console\Command;

class RegisterGoogleCalendarWatchCommand extends Command
{
    protected $signature = 'google-calendar:watch';

    protected $description = 'Register push notification watches for all connected Google Calendar accounts';

    public function handle(GoogleCalendarWatchService $watchService): int
    {
        $renewed = $watchService->renewExpiringWatches();

        $this->info("Registered/renewed {$renewed} Google Calendar watch(es).");

        return self::SUCCESS;
    }
}
