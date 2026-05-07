<?php

namespace Tests\Feature\Integrations;

use App\Models\User;
use App\Support\Security\EncryptedTokenStore;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleCalendarInboundSyncPaginationTest extends TestCase
{
    public function test_initial_inbound_sync_processes_all_pages_and_stores_final_sync_token(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => function ($request) {
                $pageToken = $request['pageToken'] ?? null;

                if ($pageToken === null) {
                    return Http::response([
                        'items' => [[
                            'id' => 'event-page-1',
                            'etag' => 'etag-page-1',
                            'status' => 'confirmed',
                            'summary' => 'Page 1 Event',
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
                        'nextPageToken' => 'page-2',
                    ], 200);
                }

                return Http::response([
                    'items' => [[
                        'id' => 'event-page-2',
                        'etag' => 'etag-page-2',
                        'status' => 'confirmed',
                        'summary' => 'Page 2 Event',
                        'updated' => '2026-05-24T03:00:00Z',
                        'start' => [
                            'dateTime' => '2026-05-24T10:00:00+07:00',
                            'timeZone' => 'Asia/Jakarta',
                        ],
                        'end' => [
                            'dateTime' => '2026-05-24T11:00:00+07:00',
                            'timeZone' => 'Asia/Jakarta',
                        ],
                    ]],
                    'nextSyncToken' => 'final-sync-token',
                ], 200);
            },
        ]);

        $user = User::factory()->active()->create();
        $connection = $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'encrypted_access_token' => app(EncryptedTokenStore::class)->encrypt('access-token'),
            'encrypted_refresh_token' => app(EncryptedTokenStore::class)->encrypt('refresh-token'),
            'token_expires_at' => now()->addHour(),
            'status' => 'connected',
            'sync_token' => null,
        ]);

        app(\App\Services\Integrations\GoogleCalendarInboundSyncService::class)->syncConnection($connection);

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Page 1 Event',
            'scheduled_date' => '2026-05-23',
            'source_channel' => 'google_calendar',
        ]);

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Page 2 Event',
            'scheduled_date' => '2026-05-24',
            'source_channel' => 'google_calendar',
        ]);

        $connection->refresh();

        $this->assertSame('final-sync-token', $connection->sync_token);
    }
}
