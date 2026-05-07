<?php

namespace Tests\Feature\Integrations;

use App\Jobs\Calendar\SyncTaskToGoogleCalendarJob;
use App\Models\CalendarEventLink;
use App\Models\CalendarSyncLog;
use App\Models\Task;
use App\Models\User;
use App\Support\Security\EncryptedTokenStore;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalendarEventLinkingTest extends TestCase
{
    public function test_sync_job_creates_google_event_link_and_success_log_for_new_task(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events' => Http::response([
                'id' => 'google-event-123',
                'etag' => 'etag-123',
                'status' => 'confirmed',
                'updated' => '2026-05-23T03:00:00Z',
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

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Calendar sync create',
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '10:00:00',
            'timezone' => 'Asia/Jakarta',
        ]);

        app()->call([new SyncTaskToGoogleCalendarJob($task->id, 'upsert'), 'handle']);

        $this->assertDatabaseHas('calendar_event_links', [
            'task_id' => $task->id,
            'user_calendar_connection_id' => $connection->id,
            'google_event_id' => 'google-event-123',
            'sync_status' => 'synced',
        ]);

        $this->assertDatabaseHas('calendar_sync_logs', [
            'user_id' => $user->id,
            'task_id' => $task->id,
            'user_calendar_connection_id' => $connection->id,
            'direction' => 'outbound',
            'action' => 'upsert',
            'status' => 'success',
        ]);
    }

    public function test_sync_job_updates_existing_google_event_link_for_existing_mapping(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events/google-event-123' => Http::response([
                'id' => 'google-event-123',
                'etag' => 'etag-456',
                'status' => 'confirmed',
                'updated' => '2026-05-23T04:00:00Z',
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

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Calendar sync update',
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '11:00:00',
            'timezone' => 'Asia/Jakarta',
        ]);

        $link = CalendarEventLink::query()->create([
            'task_id' => $task->id,
            'user_calendar_connection_id' => $connection->id,
            'google_event_id' => 'google-event-123',
            'sync_status' => 'pending',
        ]);

        app()->call([new SyncTaskToGoogleCalendarJob($task->id, 'upsert'), 'handle']);

        $link->refresh();

        $this->assertSame('etag-456', $link->google_event_etag);
        $this->assertSame('synced', $link->sync_status);
    }

    public function test_sync_job_deletes_existing_google_event_mapping_and_logs_it(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events/google-event-123' => Http::response([], 204),
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

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Calendar sync delete',
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '12:00:00',
            'timezone' => 'Asia/Jakarta',
        ]);

        $link = CalendarEventLink::query()->create([
            'task_id' => $task->id,
            'user_calendar_connection_id' => $connection->id,
            'google_event_id' => 'google-event-123',
            'sync_status' => 'pending',
        ]);

        app()->call([new SyncTaskToGoogleCalendarJob($task->id, 'delete'), 'handle']);

        $link->refresh();

        $this->assertSame('deleted', $link->remote_status);
        $this->assertSame('synced', $link->sync_status);

        $this->assertDatabaseHas('calendar_sync_logs', [
            'user_id' => $user->id,
            'task_id' => $task->id,
            'user_calendar_connection_id' => $connection->id,
            'calendar_event_link_id' => $link->id,
            'direction' => 'outbound',
            'action' => 'delete',
            'status' => 'success',
        ]);
    }
}
