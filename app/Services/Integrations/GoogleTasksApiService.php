<?php

namespace App\Services\Integrations;

use App\Models\GoogleTaskList;
use App\Models\UserCalendarConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTasksApiService
{
    private const BASE_URL = 'https://tasks.googleapis.com/tasks/v1';

    public function __construct(
        private readonly GoogleCalendarApiService $calendarApiService,
    ) {}

    /**
     * @return Collection<int, GoogleTaskList>
     */
    public function syncTaskLists(UserCalendarConnection $connection): Collection
    {
        $accessToken = $this->calendarApiService->getValidAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->get(self::BASE_URL . '/users/@me/lists');

        if (! $response->successful()) {
            Log::error('Failed to fetch Google Task lists.', [
                'connection_id' => $connection->id,
                'status' => $response->status(),
            ]);

            return collect();
        }

        $lists = collect($response->json('items', []))->values();
        $existingIds = [];

        foreach ($lists as $index => $list) {
            $listId = $list['id'] ?? null;

            if (! $listId) {
                continue;
            }

            $existingIds[] = $listId;

            $record = $connection->googleTaskLists()->firstOrNew([
                'google_task_list_id' => $listId,
            ]);

            $record->title = $list['title'] ?? $record->title;
            $record->is_default = $index === 0;
            $record->save();
        }

        if ($existingIds !== []) {
            $connection->googleTaskLists()
                ->whereNotIn('google_task_list_id', $existingIds)
                ->delete();

            $default = $connection->googleTaskLists()->where('is_default', true)->first();
            if ($default) {
                $connection->update(['google_task_list_id' => $default->google_task_list_id]);
            }
        }

        return $connection->googleTaskLists()->orderByDesc('is_default')->orderBy('title')->get();
    }

    public function getOrCreateDefaultTaskList(UserCalendarConnection $connection): ?GoogleTaskList
    {
        $defaultList = $connection->defaultGoogleTaskList()->first();

        if ($defaultList) {
            return $defaultList;
        }

        return $this->syncTaskLists($connection)->first();
    }

    public function resolveTaskList(UserCalendarConnection $connection, ?string $preferredListId = null): ?GoogleTaskList
    {
        if ($preferredListId) {
            $preferred = $connection->googleTaskLists()->where('google_task_list_id', $preferredListId)->first();
            if ($preferred) {
                return $preferred;
            }
        }

        return $this->getOrCreateDefaultTaskList($connection);
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>, task_list: GoogleTaskList|null}
     */
    public function createTask(UserCalendarConnection $connection, array $payload, ?string $preferredListId = null): array
    {
        $taskList = $this->resolveTaskList($connection, $preferredListId);

        if (! $taskList) {
            return ['ok' => false, 'status' => 0, 'data' => ['error' => 'no_task_list'], 'task_list' => null];
        }

        $accessToken = $this->calendarApiService->getValidAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->post(self::BASE_URL . "/lists/{$taskList->google_task_list_id}/tasks", $payload);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
            'task_list' => $taskList,
        ];
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>, task_list: GoogleTaskList|null}
     */
    public function updateTask(UserCalendarConnection $connection, string $taskId, array $payload, ?string $preferredListId = null): array
    {
        $taskList = $this->resolveTaskList($connection, $preferredListId);

        if (! $taskList) {
            return ['ok' => false, 'status' => 0, 'data' => ['error' => 'no_task_list'], 'task_list' => null];
        }

        $accessToken = $this->calendarApiService->getValidAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->patch(self::BASE_URL . "/lists/{$taskList->google_task_list_id}/tasks/{$taskId}", $payload);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
            'task_list' => $taskList,
        ];
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>, task_list: GoogleTaskList|null}
     */
    public function deleteTask(UserCalendarConnection $connection, string $taskId, ?string $preferredListId = null): array
    {
        $taskList = $this->resolveTaskList($connection, $preferredListId);

        if (! $taskList) {
            return ['ok' => false, 'status' => 0, 'data' => ['error' => 'no_task_list'], 'task_list' => null];
        }

        $accessToken = $this->calendarApiService->getValidAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->delete(self::BASE_URL . "/lists/{$taskList->google_task_list_id}/tasks/{$taskId}");

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
            'task_list' => $taskList,
        ];
    }

    /**
     * @return array{ok: bool, items: array<int, mixed>, next_sync_token: string|null, status: int, task_list: GoogleTaskList|null}
     */
    public function listChanges(UserCalendarConnection $connection, GoogleTaskList $taskList, ?string $syncToken = null): array
    {
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
                ->get(self::BASE_URL . "/lists/{$taskList->google_task_list_id}/tasks", $params);

            if (! $response->successful()) {
                if ($response->status() === 410 && $syncToken) {
                    return $this->listChanges($connection, $taskList, null);
                }

                return ['ok' => false, 'items' => [], 'next_sync_token' => null, 'status' => $response->status(), 'task_list' => $taskList];
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
            'task_list' => $taskList,
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
            $payload['due'] = $task->scheduled_date->format('Y-m-d') . 'T00:00:00.000Z';
        }

        if ($task->status === 'completed' && $task->completed_at) {
            $payload['completed'] = $task->completed_at->toIso8601String();
        }

        return $payload;
    }
}
