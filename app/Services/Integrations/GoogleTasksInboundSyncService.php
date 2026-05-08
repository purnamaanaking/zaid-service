<?php

namespace App\Services\Integrations;

use App\Models\CalendarEventLink;
use App\Models\CalendarSyncLog;
use App\Models\Task;
use App\Models\UserCalendarConnection;

class GoogleTasksInboundSyncService
{
    public function __construct(
        private readonly GoogleTasksApiService $tasksApi,
    ) {}

    public function syncConnection(UserCalendarConnection $connection): void
    {
        $result = $this->tasksApi->listChanges($connection, $connection->tasks_sync_token);

        if (! $result['ok']) {
            return;
        }

        foreach ($result['items'] as $item) {
            $this->applyItem($connection, $item);
        }

        if ($result['next_sync_token']) {
            $connection->update([
                'tasks_sync_token' => $result['next_sync_token'],
                'last_synced_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function applyItem(UserCalendarConnection $connection, array $item): void
    {
        $googleTaskId = $item['id'] ?? null;

        if (! $googleTaskId) {
            return;
        }

        $link = CalendarEventLink::query()
            ->where('user_calendar_connection_id', $connection->id)
            ->where('google_event_id', $googleTaskId)
            ->where('link_type', 'google_task')
            ->first();

        $linkedTask = $link?->task()->withTrashed()->first();

        // Deleted task
        if (($item['deleted'] ?? false) === true && $link && $linkedTask) {
            if (! $linkedTask->trashed()) {
                $linkedTask->delete();
            }

            $link->update([
                'remote_status' => 'deleted',
                'last_synced_at' => now(),
                'sync_status' => 'synced',
            ]);

            $this->log($connection, $linkedTask, $link, 'delete', 'success', $item);

            return;
        }

        // Hidden (completed and cleared) tasks
        if (($item['hidden'] ?? false) === true && $link && $linkedTask) {
            if ($linkedTask->status !== 'completed') {
                $linkedTask->update([
                    'status' => 'completed',
                    'completed_at' => isset($item['completed']) ? now()->parse($item['completed']) : now(),
                ]);
            }

            $link->update(['last_synced_at' => now(), 'sync_status' => 'synced']);
            $this->log($connection, $linkedTask, $link, 'update', 'success', $item);

            return;
        }

        $taskData = $this->googleTaskToLocalData($item);

        // Update existing
        if ($link && $linkedTask) {
            $linkedTask->update(array_filter([
                'title' => $taskData['title'],
                'description' => $taskData['description'],
                'scheduled_date' => $taskData['scheduled_date'],
                'status' => $taskData['status'],
                'completed_at' => $taskData['completed_at'],
            ], fn ($v) => $v !== null));

            $link->update([
                'google_event_etag' => $item['etag'] ?? null,
                'remote_status' => $item['status'] ?? null,
                'remote_updated_at' => isset($item['updated']) ? now()->parse($item['updated']) : now(),
                'last_synced_at' => now(),
                'sync_status' => 'synced',
            ]);

            $this->log($connection, $linkedTask, $link, 'update', 'success', $item);

            return;
        }

        // Create new
        $task = Task::query()->create([
            'user_id' => $connection->user_id,
            'source_channel' => 'google_tasks',
            'title' => $taskData['title'] ?? 'Untitled',
            'description' => $taskData['description'],
            'status' => $taskData['status'],
            'scheduled_date' => $taskData['scheduled_date'],
            'scheduled_time' => null,
            'timezone' => 'Asia/Jakarta',
            'all_day' => false,
            'is_recurring' => false,
            'completed_at' => $taskData['completed_at'],
        ]);

        $link = CalendarEventLink::query()->firstOrCreate(
            [
                'user_calendar_connection_id' => $connection->id,
                'google_event_id' => $googleTaskId,
            ],
            [
                'task_id' => $task->id,
                'link_type' => 'google_task',
                'google_event_etag' => $item['etag'] ?? null,
                'remote_status' => $item['status'] ?? null,
                'remote_updated_at' => isset($item['updated']) ? now()->parse($item['updated']) : now(),
                'last_synced_at' => now(),
                'last_synced_payload_hash' => hash('sha256', json_encode($item, JSON_THROW_ON_ERROR)),
                'sync_status' => 'synced',
            ],
        );

        if ((string) $link->task_id !== (string) $task->id) {
            $task->delete();

            return;
        }

        $this->log($connection, $task, $link, 'create', 'success', $item);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function googleTaskToLocalData(array $item): array
    {
        $status = ($item['status'] ?? 'needsAction') === 'completed' ? 'completed' : 'pending';
        $due = $item['due'] ?? null;
        $scheduledDate = null;

        if ($due) {
            $scheduledDate = now()->parse($due)->format('Y-m-d');
        }

        return [
            'title' => $item['title'] ?? null,
            'description' => $item['notes'] ?? null,
            'scheduled_date' => $scheduledDate,
            'status' => $status,
            'completed_at' => $status === 'completed' && isset($item['completed'])
                ? now()->parse($item['completed'])
                : null,
        ];
    }

    private function log(UserCalendarConnection $connection, Task $task, CalendarEventLink $link, string $action, string $status, array $context): void
    {
        CalendarSyncLog::query()->create([
            'user_id' => $connection->user_id,
            'task_id' => $task->id,
            'user_calendar_connection_id' => $connection->id,
            'calendar_event_link_id' => $link->id,
            'direction' => 'inbound',
            'action' => $action,
            'status' => $status,
            'context' => $context,
            'logged_at' => now(),
        ]);
    }
}
