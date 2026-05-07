<?php

namespace App\Jobs\Calendar;

use App\Models\UserCalendarConnection;
use App\Services\Integrations\GoogleCalendarInboundSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGoogleCalendarConnectionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $connectionId) {}

    public function handle(GoogleCalendarInboundSyncService $syncService): void
    {
        $connection = UserCalendarConnection::query()->findOrFail($this->connectionId);

        $syncService->syncConnection($connection);
    }
}
