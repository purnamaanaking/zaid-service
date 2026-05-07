<?php

namespace Tests\Feature\Integrations;

use App\Models\Task;
use App\Models\User;
use App\Support\Security\EncryptedTokenStore;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleCalendarFailureModesTest extends TestCase
{
    public function test_expired_sync_token_marks_connection_errored_when_google_rejects_incremental_sync(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
                'error' => [
                    'message' => 'Sync token is no longer valid, a full sync is required.',
                ],
            ], 410),
        ]);

        $user = User::factory()->active()->create();
        $connection = $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'encrypted_access_token' => app(EncryptedTokenStore::class)->encrypt('access-token'),
            'encrypted_refresh_token' => app(EncryptedTokenStore::class)->encrypt('refresh-token'),
            'token_expires_at' => now()->addHour(),
            'status' => 'connected',
            'sync_token' => 'stale-sync-token',
        ]);

        app(\App\Services\Integrations\GoogleCalendarInboundSyncService::class)->syncConnection($connection);

        $connection->refresh();

        $this->assertSame('error', $connection->status);
        $this->assertNotNull($connection->last_error_message);
    }

    public function test_failed_refresh_token_marks_connection_reconnect_required(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Token has been expired or revoked.',
            ], 400),
        ]);

        $user = User::factory()->active()->create();
        $connection = $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'encrypted_access_token' => app(EncryptedTokenStore::class)->encrypt('expired-access-token'),
            'encrypted_refresh_token' => app(EncryptedTokenStore::class)->encrypt('revoked-refresh-token'),
            'token_expires_at' => now()->subMinute(),
            'status' => 'connected',
        ]);

        try {
            app(\App\Services\Integrations\GoogleCalendarApiService::class)->getValidAccessToken($connection);
        } catch (\Illuminate\Validation\ValidationException) {
            // Expected for now; state update is what matters.
        }

        $connection->refresh();

        $this->assertSame('revoked', $connection->status);
        $this->assertNotNull($connection->last_error_message);
    }

    public function test_unconnected_user_task_mutations_do_not_create_calendar_side_effects(): void
    {
        Http::fake();

        $user = User::factory()->active()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '10:00:00',
        ]);

        $this->assertDatabaseMissing('calendar_event_links', [
            'task_id' => $task->id,
        ]);
    }
}
