<?php

namespace Tests\Feature\Integrations;

use App\Models\User;
use App\Support\Security\EncryptedTokenStore;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleCalendarApiServiceTest extends TestCase
{
    public function test_it_lists_google_calendar_changes_using_sync_token(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
                'items' => [['id' => 'event-1', 'summary' => 'Task 1']],
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
        ]);

        $result = app(\App\Services\Integrations\GoogleCalendarApiService::class)->listChanges($connection, 'sync-token-1');

        $this->assertTrue($result['ok']);
        $this->assertSame('sync-token-2', $result['next_sync_token']);
        $this->assertCount(1, $result['items']);
    }

    public function test_it_refreshes_expired_access_token_before_requesting_google_calendar(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
                'items' => [],
                'nextSyncToken' => 'sync-token-2',
            ], 200),
        ]);

        $user = User::factory()->active()->create();
        $connection = $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'encrypted_access_token' => app(EncryptedTokenStore::class)->encrypt('expired-access-token'),
            'encrypted_refresh_token' => app(EncryptedTokenStore::class)->encrypt('refresh-token'),
            'token_expires_at' => now()->subMinute(),
            'status' => 'connected',
        ]);

        $result = app(\App\Services\Integrations\GoogleCalendarApiService::class)->listChanges($connection);

        $this->assertTrue($result['ok']);
        $this->assertNotNull($connection->fresh()->encrypted_access_token);

        Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/token');
    }
}
