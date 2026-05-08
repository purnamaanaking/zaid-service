<?php

namespace App\Services\Integrations;

use App\Models\UserCalendarConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTasksApiService
{
    private const BASE_URL = 'https://tasks.googleapis.com/tasks/v1';

    public function __construct(
        private readonly GoogleCalendarApiService $calendarApiService,
    ) {}

    public function getOrCreateDefaultTaskList(UserCalendarConnection $connection): ?string
    {
        if ($connection->google_task_list_id) {
            return $connection->google_task_list_id;
        }

        $accessToken = $this->calendarApiService->getValidAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->get(self::BASE_URL . '/users/@me/lists');

        if (! $response->successful()) {
            Log::error('Failed to fetch Google Task lists.', [
                'connection_id' => $connection->id,
                'status' => $response->status(),
            ]);

            return null;
        }

        $lists = $response->json('items', []);
        $defaultList = collect($lists)->first();

        if (! $defaultList) {
            return null;
        }

        $listId = $defaultList['id'];
        $connection->update(['google_task_list_id' => $listId]);

        return $listId;
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>}
     */
    public function createTask(UserCalendarConnection $connection, array $payload): array
    {
        $listId = $this->getOrCreateDefaultTaskList($connection);

        if (! $listId) {
            return ['ok' => false, 'status' => 0, 'data' => ['error' => 'no_task_list']];
        }

        $accessToken = $this->calendarApiService->getValidAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->post(self::BASE_URL . "/lists/{$listId}/tasks", $payload);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
        ];
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>}
     */
    public function updateTask(UserCalendarConnection $connection, string $taskId, array $payload): array
    {
        $listId = $connection->google_task_list_id;

        if (! $listId) {
            return ['ok' => false, 'status' => 0, 'data' => ['error' => 'no_task_list']];
        }

        $accessToken = $this->calendarApiService->getValidAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->patch(self::BASE_URL . "/lists/{$listId}/tasks/{$taskId}", $payload);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
        ];
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>}
     */
    public function deleteTask(UserCalendarConnection $connection, string $taskId): array
    {
        $listId = $connection->google_task_list_id;

        if (! $listId) {
            return ['ok' => false, 'status' => 0, 'data' => ['error' => 'no_task_list']];
        }

        $accessToken = $this->calendarApiService->getValidAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->delete(self::BASE_URL . "/lists/{$listId}/tasks/{$taskId}");

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
        ];
    }

    /**
     * @return array{ok: bool, items: array<int, mixed>, next_sync_token: string|null, status: int}
     */
    public function listChanges(UserCalendarConnection $connection, ?string $syncToken = null): array
    {
        $listId = $this->getOrCreateDefaultTaskList($connection);

        if (! $listId) {
            return ['ok' => false, 'items' => [], 'next_sync_token' => null, 'status' => 0];
        }

        $accessToken = $this->calendarApiService->getValidAccessToken($connection);
        $items = [];
        $nextPageToken = null;
        $nextSyncToken = null;

        do {
            $params = array_filter([
                'showCompleted' => 'true',
                'showDeleted' => 'true',
                'showHidden' => 'true',
                'syncToken' => $syncToken,
                'pageToken' => $nextPageToken,
            ], fn ($v) => $v !== null);

            $response = Http::withToken($accessToken)
                ->get(self::BASE_URL . "/lists/{$listId}/tasks", $params);

            if (! $response->successful()) {
                if ($response->status() === 410 && $syncToken) {
                    // Sync token expired, do full sync
                    return $this->listChanges($connection, null);
                }

                return ['ok' => false, 'items' => [], 'next_sync_token' => null, 'status' => $response->status()];
            }

            $items = array_merge($items, $response->json('items', []));
            $nextPageToken = $response->json('nextPageToken');
            $nextSyncToken = $response->json('nextSyncToken', $nextSyncToken);
        } while ($nextPageToken);

        return [
            'ok' => true,
            'items' => $items,
            'next_sync_token' => $nextSyncToken,
            'status' => 200,
        ];
    }

    /**
     * Transform a local task into a Google Tasks API payload.
     */
    public function taskToGoogleTask(\App\Models\Task $task): array
    {
        $payload = [
            'title' => $task->title,
            'notes' => $task->description,
            'status' => $task->status === 'completed' ? 'completed' : 'needsAction',
        ];

        if ($task->scheduled_date) {
            // Google Tasks uses RFC 3339 date for 'due'
            $payload['due'] = $task->scheduled_date->format('Y-m-d') . 'T00:00:00.000Z';
        }

        if ($task->status === 'completed' && $task->completed_at) {
            $payload['completed'] = $task->completed_at->toIso8601String();
        }

        return $payload;
    }
}
