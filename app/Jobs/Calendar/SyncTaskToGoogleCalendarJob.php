<?php

namespace App\Jobs\Calendar;

use App\Models\CalendarEventLink;
use App\Models\CalendarSyncLog;
use App\Models\Task;
use App\Services\Integrations\GoogleCalendarApiService;
use App\Services\Integrations\GoogleCalendarEventTransformer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncTaskToGoogleCalendarJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $taskId,
        public readonly string $action,
    ) {}

    public function handle(
        GoogleCalendarApiService $googleCalendarApiService,
        GoogleCalendarEventTransformer $transformer,
    ): void {
        $task = Task::withTrashed()->with(['user.calendarConnections', 'calendarEventLink', 'recurrence'])->findOrFail($this->taskId);
        $connection = $task->user->calendarConnections()
            ->where('provider', 'google_calendar')
            ->where('status', 'connected')
            ->first();

        if (! $connection) {
            return;
        }

        $link = $task->calendarEventLink;

        if ($this->action === 'delete' && $link) {
            $response = $googleCalendarApiService->deleteEvent($connection, $link->google_event_id);

            $link->update([
                'remote_status' => 'deleted',
                'last_synced_at' => now(),
                'sync_status' => $response['ok'] ? 'synced' : 'failed',
                'sync_error' => $response['ok'] ? null : json_encode($response['data']),
            ]);

            CalendarSyncLog::query()->create([
                'user_id' => $task->user_id,
                'task_id' => $task->id,
                'user_calendar_connection_id' => $connection->id,
                'calendar_event_link_id' => $link->id,
                'direction' => 'outbound',
                'action' => 'delete',
                'status' => $response['ok'] ? 'success' : 'failed',
                'context' => $response['data'],
                'error_message' => $response['ok'] ? null : json_encode($response['data']),
                'logged_at' => now(),
            ]);

            return;
        }

        $payload = $transformer->taskToGoogleEvent($task);
        $response = $link
            ? $googleCalendarApiService->updateEvent($connection, $link->google_event_id, $payload)
            : $googleCalendarApiService->createEvent($connection, $payload);

        $eventData = $response['data'];

        $link = CalendarEventLink::query()->updateOrCreate(
            ['task_id' => $task->id],
            [
                'user_calendar_connection_id' => $connection->id,
                'google_event_id' => $eventData['id'] ?? $link?->google_event_id,
                'google_event_etag' => $eventData['etag'] ?? null,
                'remote_status' => $eventData['status'] ?? null,
                'remote_updated_at' => isset($eventData['updated']) ? now()->parse($eventData['updated']) : null,
                'last_synced_at' => now(),
                'last_synced_payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                'sync_status' => $response['ok'] ? 'synced' : 'failed',
                'sync_error' => $response['ok'] ? null : json_encode($eventData),
            ],
        );

        CalendarSyncLog::query()->create([
            'user_id' => $task->user_id,
            'task_id' => $task->id,
            'user_calendar_connection_id' => $connection->id,
            'calendar_event_link_id' => $link->id,
            'direction' => 'outbound',
            'action' => $this->action,
            'status' => $response['ok'] ? 'success' : 'failed',
            'context' => $eventData,
            'error_message' => $response['ok'] ? null : json_encode($eventData),
            'logged_at' => now(),
        ]);
    }
}
