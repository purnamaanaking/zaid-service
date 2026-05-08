<?php

namespace Tests\Feature\Integrations;

use App\Jobs\Calendar\SyncGoogleTasksConnectionJob;
use App\Jobs\Calendar\SyncTaskToGoogleTasksJob;
use App\Models\Task;
use App\Models\User;
use App\Support\Security\EncryptedTokenStore;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleTasksMultiListSyncTest extends TestCase
{
    public function test_inbound_sync_imports_tasks_from_multiple_google_task_lists_and_persists_per_list_tokens(): void
    {
        Http::fake([
            'https://tasks.googleapis.com/tasks/v1/users/@me/lists' => Http::response([
                'items' => [
                    ['id' => 'list-my-tasks', 'title' => 'My Tasks'],
                    ['id' => 'list-kuliah', 'title' => 'KULIAH'],
                ],
            ], 200),
            'https://tasks.googleapis.com/tasks/v1/lists/list-my-tasks/tasks*' => Http::response([
                'items' => [[
                    'id' => 'task-1',
                    'title' => 'Penelitian',
                    'status' => 'needsAction',
                    'due' => '2026-05-08T00:00:00.000Z',
                    'updated' => '2026-05-08T01:00:00.000Z',
                    'etag' => 'etag-1',
                ]],
                'nextSyncToken' => 'sync-my-tasks',
            ], 200),
            'https://tasks.googleapis.com/tasks/v1/lists/list-kuliah/tasks*' => Http::response([
                'items' => [[
                    'id' => 'task-2',
                    'title' => 'skema api',
                    'status' => 'needsAction',
                    'updated' => '2026-05-08T02:00:00.000Z',
                    'etag' => 'etag-2',
                ]],
                'nextSyncToken' => 'sync-kuliah',
            ], 200),
        ]);

        $user = User::factory()->active()->create();
        $connection = $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'encrypted_access_token' => app(EncryptedTokenStore::class)->encrypt('access-token'),
            'encrypted_refresh_token' => app(EncryptedTokenStore::class)->encrypt('refresh-token'),
            'token_expires_at' => now()->addHour(),
            'status' => 'connected',
        ]);

        app()->call([new SyncGoogleTasksConnectionJob($connection->id), 'handle']);

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Penelitian',
            'source_channel' => 'google_tasks',
            'google_task_list_id' => 'list-my-tasks',
            'google_task_list_title' => 'My Tasks',
        ]);

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'skema api',
            'source_channel' => 'google_tasks',
            'google_task_list_id' => 'list-kuliah',
            'google_task_list_title' => 'KULIAH',
        ]);

        $this->assertDatabaseHas('google_task_lists', [
            'user_calendar_connection_id' => $connection->id,
            'google_task_list_id' => 'list-my-tasks',
            'title' => 'My Tasks',
            'sync_token' => 'sync-my-tasks',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('google_task_lists', [
            'user_calendar_connection_id' => $connection->id,
            'google_task_list_id' => 'list-kuliah',
            'title' => 'KULIAH',
            'sync_token' => 'sync-kuliah',
            'is_default' => false,
        ]);
    }

    public function test_outbound_google_task_create_uses_default_task_list_and_records_the_selected_list(): void
    {
        Http::fake([
            'https://tasks.googleapis.com/tasks/v1/lists/list-kuliah/tasks' => Http::response([
                'id' => 'remote-task-1',
                'title' => 'Penelitian',
                'status' => 'needsAction',
                'updated' => '2026-05-08T03:00:00.000Z',
                'etag' => 'etag-outbound-1',
            ], 200),
        ]);

        $user = User::factory()->active()->create();
        $connection = $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'google_task_list_id' => 'list-kuliah',
            'encrypted_access_token' => app(EncryptedTokenStore::class)->encrypt('access-token'),
            'encrypted_refresh_token' => app(EncryptedTokenStore::class)->encrypt('refresh-token'),
            'token_expires_at' => now()->addHour(),
            'status' => 'connected',
        ]);

        $connection->googleTaskLists()->create([
            'google_task_list_id' => 'list-kuliah',
            'title' => 'KULIAH',
            'is_default' => true,
        ]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Penelitian',
            'scheduled_date' => null,
            'scheduled_time' => null,
            'source_channel' => 'app_manual',
            'google_task_list_id' => null,
            'google_task_list_title' => null,
        ]);

        app()->call([new SyncTaskToGoogleTasksJob($task->id, 'upsert'), 'handle']);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://tasks.googleapis.com/tasks/v1/lists/list-kuliah/tasks';
        });

        $task->refresh();
        $this->assertSame('list-kuliah', $task->google_task_list_id);
        $this->assertSame('KULIAH', $task->google_task_list_title);

        $this->assertDatabaseHas('calendar_event_links', [
            'task_id' => $task->id,
            'user_calendar_connection_id' => $connection->id,
            'link_type' => 'google_task',
            'google_event_id' => 'remote-task-1',
            'google_task_list_id' => 'list-kuliah',
        ]);
    }

    public function test_outbound_google_task_update_uses_the_existing_task_list_assignment(): void
    {
        Http::fake([
            'https://tasks.googleapis.com/tasks/v1/lists/list-sadhana/tasks/remote-task-2' => Http::response([
                'id' => 'remote-task-2',
                'title' => 'Ui integrasi',
                'status' => 'needsAction',
                'updated' => '2026-05-08T04:00:00.000Z',
                'etag' => 'etag-outbound-2',
            ], 200),
        ]);

        $user = User::factory()->active()->create();
        $connection = $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'google_task_list_id' => 'list-my-tasks',
            'encrypted_access_token' => app(EncryptedTokenStore::class)->encrypt('access-token'),
            'encrypted_refresh_token' => app(EncryptedTokenStore::class)->encrypt('refresh-token'),
            'token_expires_at' => now()->addHour(),
            'status' => 'connected',
        ]);

        $connection->googleTaskLists()->createMany([
            [
                'google_task_list_id' => 'list-my-tasks',
                'title' => 'My Tasks',
                'is_default' => true,
            ],
            [
                'google_task_list_id' => 'list-sadhana',
                'title' => 'Sadhana',
                'is_default' => false,
            ],
        ]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Ui integrasi',
            'scheduled_date' => null,
            'scheduled_time' => null,
            'source_channel' => 'google_tasks',
            'google_task_list_id' => 'list-sadhana',
            'google_task_list_title' => 'Sadhana',
        ]);

        $task->calendarEventLink()->create([
            'user_calendar_connection_id' => $connection->id,
            'link_type' => 'google_task',
            'google_event_id' => 'remote-task-2',
            'google_task_list_id' => 'list-sadhana',
            'sync_status' => 'synced',
        ]);

        app()->call([new SyncTaskToGoogleTasksJob($task->id, 'upsert'), 'handle']);

        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH'
                && $request->url() === 'https://tasks.googleapis.com/tasks/v1/lists/list-sadhana/tasks/remote-task-2';
        });
    }
}
