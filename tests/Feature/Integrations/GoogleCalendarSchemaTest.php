<?php

namespace Tests\Feature\Integrations;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GoogleCalendarSchemaTest extends TestCase
{
    public function test_google_calendar_sync_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('user_calendar_connections'));
        $this->assertTrue(Schema::hasTable('calendar_event_links'));
        $this->assertTrue(Schema::hasTable('calendar_sync_logs'));
    }

    public function test_user_calendar_connections_table_contains_required_columns(): void
    {
        foreach ([
            'user_id',
            'provider',
            'google_calendar_id',
            'google_calendar_summary',
            'encrypted_access_token',
            'encrypted_refresh_token',
            'token_expires_at',
            'scopes',
            'sync_token',
            'status',
            'last_synced_at',
            'last_error_at',
            'last_error_message',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('user_calendar_connections', $column), $column);
        }
    }

    public function test_calendar_event_links_table_contains_required_columns(): void
    {
        foreach ([
            'task_id',
            'user_calendar_connection_id',
            'google_event_id',
            'google_event_etag',
            'remote_status',
            'remote_updated_at',
            'last_synced_at',
            'last_synced_payload_hash',
            'sync_status',
            'sync_error',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('calendar_event_links', $column), $column);
        }
    }

    public function test_calendar_sync_logs_table_contains_required_columns(): void
    {
        foreach ([
            'user_id',
            'task_id',
            'user_calendar_connection_id',
            'calendar_event_link_id',
            'direction',
            'action',
            'status',
            'context',
            'error_message',
            'logged_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('calendar_sync_logs', $column), $column);
        }
    }

    public function test_user_and_task_relationships_to_calendar_sync_models_work(): void
    {
        $user = User::factory()->active()->create();
        $task = Task::query()->create([
            'user_id' => $user->id,
            'source_channel' => 'app_manual',
            'title' => 'Calendar sync test task',
            'status' => 'pending',
            'timezone' => 'Asia/Jakarta',
            'all_day' => false,
            'is_recurring' => false,
        ]);

        $connection = $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'google_calendar_summary' => 'Primary Calendar',
            'encrypted_access_token' => 'enc-access',
            'encrypted_refresh_token' => 'enc-refresh',
            'token_expires_at' => now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/calendar'],
            'status' => 'connected',
        ]);

        $link = $task->calendarEventLink()->create([
            'user_calendar_connection_id' => $connection->id,
            'google_event_id' => 'event_123',
            'google_event_etag' => 'etag_123',
            'remote_status' => 'confirmed',
            'remote_updated_at' => now(),
            'last_synced_at' => now(),
            'last_synced_payload_hash' => 'hash_123',
            'sync_status' => 'synced',
        ]);

        $log = $user->calendarSyncLogs()->create([
            'task_id' => $task->id,
            'user_calendar_connection_id' => $connection->id,
            'calendar_event_link_id' => $link->id,
            'direction' => 'outbound',
            'action' => 'create',
            'status' => 'success',
            'context' => ['source' => 'test'],
            'logged_at' => now(),
        ]);

        $this->assertTrue($user->calendarConnections()->whereKey($connection->id)->exists());
        $this->assertTrue($task->calendarEventLink()->whereKey($link->id)->exists());
        $this->assertTrue($user->calendarSyncLogs()->whereKey($log->id)->exists());
    }
}
