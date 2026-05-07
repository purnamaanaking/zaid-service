<?php

namespace Tests\Feature\Integrations;

use App\Jobs\Calendar\SyncGoogleCalendarConnectionJob;
use App\Models\Task;
use App\Models\User;
use App\Support\Security\EncryptedTokenStore;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleCalendarInboundSyncTest extends TestCase
{
    public function test_remote_google_event_creates_local_task_and_persists_new_sync_token(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
                'items' => [[
                    'id' => 'event-123',
                    'etag' => 'etag-123',
                    'status' => 'confirmed',
                    'summary' => 'Imported Event',
                    'description' => 'From Google Calendar',
                    'updated' => '2026-05-23T03:00:00Z',
                    'start' => [
                        'dateTime' => '2026-05-23T10:00:00+07:00',
                        'timeZone' => 'Asia/Jakarta',
                    ],
                    'end' => [
                        'dateTime' => '2026-05-23T11:00:00+07:00',
                        'timeZone' => 'Asia/Jakarta',
                    ],
                ]],
                'nextSyncToken' => 'sync-token-2',
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
            'sync_token' => 'sync-token-1',
        ]);

        app()->call([new SyncGoogleCalendarConnectionJob($connection->id), 'handle']);

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Imported Event',
            'description' => 'From Google Calendar',
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '10:00:00',
            'source_channel' => 'google_calendar',
        ]);

        $connection->refresh();
        $this->assertSame('sync-token-2', $connection->sync_token);
    }

    public function test_remote_google_event_updates_existing_local_task(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
                'items' => [[
                    'id' => 'event-123',
                    'etag' => 'etag-999',
                    'status' => 'confirmed',
                    'summary' => 'Updated From Google',
                    'description' => 'Changed remotely',
                    'updated' => '2026-05-23T05:00:00Z',
                    'start' => [
                        'dateTime' => '2026-05-23T12:00:00+07:00',
                        'timeZone' => 'Asia/Jakarta',
                    ],
                    'end' => [
                        'dateTime' => '2026-05-23T13:00:00+07:00',
                        'timeZone' => 'Asia/Jakarta',
                    ],
                ]],
                'nextSyncToken' => 'sync-token-3',
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
            'sync_token' => 'sync-token-2',
        ]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Old Title',
            'description' => 'Old desc',
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '10:00:00',
            'timezone' => 'Asia/Jakarta',
            'source_channel' => 'google_calendar',
        ]);

        $task->calendarEventLink()->create([
            'user_calendar_connection_id' => $connection->id,
            'google_event_id' => 'event-123',
            'google_event_etag' => 'etag-old',
            'remote_status' => 'confirmed',
            'sync_status' => 'synced',
        ]);

        app()->call([new SyncGoogleCalendarConnectionJob($connection->id), 'handle']);

        $task->refresh();

        $this->assertSame('Updated From Google', $task->title);
        $this->assertSame('Changed remotely', $task->description);
        $this->assertSame('12:00:00', $task->scheduled_time);
    }

    public function test_remote_cancelled_google_event_soft_deletes_local_task(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
                'items' => [[
                    'id' => 'event-123',
                    'etag' => 'etag-del',
                    'status' => 'cancelled',
                    'summary' => 'Deleted Event',
                    'updated' => '2026-05-23T06:00:00Z',
                    'start' => [
                        'dateTime' => '2026-05-23T14:00:00+07:00',
                        'timeZone' => 'Asia/Jakarta',
                    ],
                    'end' => [
                        'dateTime' => '2026-05-23T15:00:00+07:00',
                        'timeZone' => 'Asia/Jakarta',
                    ],
                ]],
                'nextSyncToken' => 'sync-token-4',
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
            'sync_token' => 'sync-token-3',
        ]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Delete Me',
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '14:00:00',
            'timezone' => 'Asia/Jakarta',
            'source_channel' => 'google_calendar',
        ]);

        $task->calendarEventLink()->create([
            'user_calendar_connection_id' => $connection->id,
            'google_event_id' => 'event-123',
            'google_event_etag' => 'etag-old',
            'remote_status' => 'confirmed',
            'sync_status' => 'synced',
        ]);

        app()->call([new SyncGoogleCalendarConnectionJob($connection->id), 'handle']);

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }
}
