<?php

namespace App\Jobs\Calendar;

use App\Models\CalendarEventLink;
use App\Models\CalendarSyncLog;
use App\Models\Task;
use App\Services\Integrations\GoogleTasksApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncTaskToGoogleTasksJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $taskId,
        public readonly string $action,
    ) {}

    public function handle(GoogleTasksApiService $tasksApi): void
    {
        $task = Task::withTrashed()->with(['user.calendarConnections', 'calendarEventLink'])->findOrFail($this->taskId);
        $connection = $task->user->calendarConnections()
            ->where('provider', 'google_calendar')
            ->where('status', 'connected')
            ->first();

        if (! $connection) {
            return;
        }

        $link = $task->calendarEventLink;

        // Only handle links of type 'google_task' or new ones
        if ($link && $link->link_type !== 'google_task') {
            return;
        }

        if ($this->action === 'delete' && $link) {
            $response = $tasksApi->deleteTask($connection, $link->google_event_id);

            $link->update([
                'remote_status' => 'deleted',
                'last_synced_at' => now(),
                'sync_status' => $response['ok'] ? 'synced' : 'failed',
                'sync_error' => $response['ok'] ? null : json_encode($response['data']),
            ]);

            $this->log($connection, $task, $link, 'delete', $response['ok'] ? 'success' : 'failed', $response['data']);

            return;
        }

        $payload = $tasksApi->taskToGoogleTask($task);

        $response = $link
            ? $tasksApi->updateTask($connection, $link->google_event_id, $payload)
            : $tasksApi->createTask($connection, $payload);

        $data = $response['data'];

        $link = CalendarEventLink::query()->updateOrCreate(
            ['task_id' => $task->id],
            [
                'user_calendar_connection_id' => $connection->id,
                'link_type' => 'google_task',
                'google_event_id' => $data['id'] ?? $link?->google_event_id ?? '',
                'google_event_etag' => $data['etag'] ?? null,
                'remote_status' => $data['status'] ?? null,
                'remote_updated_at' => isset($data['updated']) ? now()->parse($data['updated']) : now(),
                'last_synced_at' => now(),
                'last_synced_payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                'sync_status' => $response['ok'] ? 'synced' : 'failed',
                'sync_error' => $response['ok'] ? null : json_encode($data),
            ],
        );

        $this->log($connection, $task, $link, $this->action, $response['ok'] ? 'success' : 'failed', $data);
    }

    private function log($connection, $task, $link, string $action, string $status, array $context): void
    {
        CalendarSyncLog::query()->create([
            'user_id' => $task->user_id,
            'task_id' => $task->id,
            'user_calendar_connection_id' => $connection->id,
            'calendar_event_link_id' => $link->id,
            'direction' => 'outbound',
            'action' => $action,
            'status' => $status,
            'context' => $context,
            'error_message' => $status === 'failed' ? json_encode($context) : null,
            'logged_at' => now(),
        ]);
    }
}
