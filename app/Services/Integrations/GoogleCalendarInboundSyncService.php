<?php

namespace App\Services\Integrations;

use App\Models\CalendarEventLink;
use App\Models\CalendarSyncLog;
use App\Models\Task;
use App\Models\UserCalendarConnection;

class GoogleCalendarInboundSyncService
{
    public function __construct(
        private readonly GoogleCalendarApiService $googleCalendarApiService,
        private readonly GoogleCalendarEventTransformer $transformer,
        private readonly CalendarSyncConflictResolver $conflictResolver,
    ) {}

    public function syncConnection(UserCalendarConnection $connection): void
    {
        $result = $this->googleCalendarApiService->listChanges($connection, $connection->sync_token);

        if (! $result['ok']) {
            return;
        }

        foreach ($result['items'] as $event) {
            $this->applyEvent($connection, $event);
        }

        if ($result['next_sync_token']) {
            $connection->update([
                'sync_token' => $result['next_sync_token'],
                'last_synced_at' => now(),
                'status' => 'connected',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function applyEvent(UserCalendarConnection $connection, array $event): void
    {
        $link = CalendarEventLink::query()
            ->where('user_calendar_connection_id', $connection->id)
            ->where('google_event_id', $event['id'])
            ->first();

        if (! $link) {
            $link = CalendarEventLink::query()
                ->where('user_calendar_connection_id', $connection->id)
                ->where('google_event_id', $event['id'])
                ->with('task')
                ->first();
        }

        $linkedTask = $link?->task()->withTrashed()->first();

        if (($event['status'] ?? 'confirmed') === 'cancelled' && $link && $linkedTask) {
            if (! $linkedTask->trashed()) {
                $linkedTask->delete();
            }

            $link->update([
                'google_event_etag' => $event['etag'] ?? null,
                'remote_status' => 'cancelled',
                'remote_updated_at' => isset($event['updated']) ? now()->parse($event['updated']) : null,
                'last_synced_at' => now(),
                'sync_status' => 'synced',
                'sync_error' => null,
            ]);

            $this->log($connection, $linkedTask, $link, 'delete', 'success', $event);

            return;
        }

        $taskData = $this->transformer->googleEventToTaskData($event);

        if ($link && $linkedTask) {
            $task = $linkedTask;
            $remoteUpdatedAt = isset($event['updated']) ? now()->parse($event['updated']) : null;
            $decision = $this->conflictResolver->resolve(
                $link->last_synced_at,
                $task->updated_at,
                $remoteUpdatedAt,
            );

            if ($decision['has_conflict']) {
                $this->log($connection, $task, $link, 'conflict', 'resolved_remote_wins', [
                    'event' => $event,
                    'resolution' => $decision['resolution'],
                ]);
            }

            if ($decision['resolution'] === 'remote_wins') {
                $task->update([
                    'title' => $taskData['title'],
                    'description' => $taskData['description'],
                    'scheduled_date' => $taskData['scheduled_date'],
                    'scheduled_time' => $taskData['scheduled_time'],
                    'timezone' => $taskData['timezone'],
                    'all_day' => $taskData['all_day'],
                    'status' => $taskData['status'],
                ]);
            }

            $link->update([
                'google_event_etag' => $event['etag'] ?? null,
                'remote_status' => $event['status'] ?? null,
                'remote_updated_at' => $remoteUpdatedAt,
                'last_synced_at' => now(),
                'sync_status' => 'synced',
                'sync_error' => null,
            ]);

            $this->log($connection, $task, $link, 'update', 'success', $event);

            return;
        }

        $task = Task::query()->create([
            'user_id' => $connection->user_id,
            'source_channel' => 'google_calendar',
            'title' => $taskData['title'],
            'description' => $taskData['description'],
            'status' => $taskData['status'],
            'scheduled_date' => $taskData['scheduled_date'],
            'scheduled_time' => $taskData['scheduled_time'],
            'timezone' => $taskData['timezone'],
            'all_day' => $taskData['all_day'],
            'is_recurring' => false,
        ]);

        $link = CalendarEventLink::query()->firstOrCreate(
            [
                'user_calendar_connection_id' => $connection->id,
                'google_event_id' => $event['id'],
            ],
            [
                'task_id' => $task->id,
                'google_event_etag' => $event['etag'] ?? null,
                'remote_status' => $event['status'] ?? null,
                'remote_updated_at' => isset($event['updated']) ? now()->parse($event['updated']) : null,
                'last_synced_at' => now(),
                'last_synced_payload_hash' => hash('sha256', json_encode($event, JSON_THROW_ON_ERROR)),
                'sync_status' => 'synced',
                'sync_error' => null,
            ],
        );

        if ((string) $link->task_id !== (string) $task->id) {
            $task->delete();

            $link->update([
                'google_event_etag' => $event['etag'] ?? null,
                'remote_status' => $event['status'] ?? null,
                'remote_updated_at' => isset($event['updated']) ? now()->parse($event['updated']) : null,
                'last_synced_at' => now(),
                'last_synced_payload_hash' => hash('sha256', json_encode($event, JSON_THROW_ON_ERROR)),
                'sync_status' => 'synced',
                'sync_error' => null,
            ]);

            if ($linkedTask) {
                $this->log($connection, $linkedTask, $link, 'update', 'success', $event);
            }

            return;
        }

        $this->log($connection, $task, $link, 'create', 'success', $event);
    }

    /**
     * @param  array<string, mixed>  $context
     */
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
