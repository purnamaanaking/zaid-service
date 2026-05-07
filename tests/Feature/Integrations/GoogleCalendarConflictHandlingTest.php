<?php

namespace Tests\Feature\Integrations;

use App\Jobs\Calendar\SyncGoogleCalendarConnectionJob;
use App\Models\Task;
use App\Models\User;
use App\Support\Security\EncryptedTokenStore;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleCalendarConflictHandlingTest extends TestCase
{
    public function test_inbound_sync_logs_conflict_and_applies_remote_last_write_wins(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
                'items' => [[
                    'id' => 'event-123',
                    'etag' => 'etag-conflict',
                    'status' => 'confirmed',
                    'summary' => 'Remote Wins Title',
                    'description' => 'Changed remotely',
                    'updated' => now()->toIso8601String(),
                    'start' => [
                        'dateTime' => '2026-05-23T15:00:00+07:00',
                        'timeZone' => 'Asia/Jakarta',
                    ],
                    'end' => [
                        'dateTime' => '2026-05-23T16:00:00+07:00',
                        'timeZone' => 'Asia/Jakarta',
                    ],
                ]],
                'nextSyncToken' => 'sync-token-conflict',
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
            'title' => 'Local Changed Title',
            'description' => 'Changed locally',
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '10:00:00',
            'timezone' => 'Asia/Jakarta',
            'source_channel' => 'app_manual',
            'updated_at' => now()->subMinutes(1),
        ]);

        $task->calendarEventLink()->create([
            'user_calendar_connection_id' => $connection->id,
            'google_event_id' => 'event-123',
            'google_event_etag' => 'etag-old',
            'remote_status' => 'confirmed',
            'remote_updated_at' => now()->subMinutes(2),
            'last_synced_at' => now()->subMinutes(5),
            'sync_status' => 'synced',
        ]);

        app()->call([new SyncGoogleCalendarConnectionJob($connection->id), 'handle']);

        $task->refresh();

        $this->assertSame('Remote Wins Title', $task->title);

        $this->assertDatabaseHas('calendar_sync_logs', [
            'user_id' => $user->id,
            'task_id' => $task->id,
            'direction' => 'inbound',
            'action' => 'conflict',
            'status' => 'resolved_remote_wins',
        ]);
    }
}
