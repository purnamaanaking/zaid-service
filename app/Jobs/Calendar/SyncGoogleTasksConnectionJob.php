<?php

namespace App\Jobs\Calendar;

use App\Models\UserCalendarConnection;
use App\Services\Integrations\GoogleTasksInboundSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGoogleTasksConnectionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $connectionId) {}

    public function handle(GoogleTasksInboundSyncService $syncService): void
    {
        $connection = UserCalendarConnection::query()->findOrFail($this->connectionId);

        $syncService->syncConnection($connection);
    }
}
