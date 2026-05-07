<?php

namespace App\Services\Integrations;

use App\Models\UserCalendarConnection;
use App\Support\Security\EncryptedTokenStore;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GoogleCalendarApiService
{
    public function __construct(
        private readonly EncryptedTokenStore $tokenStore,
    ) {}

    /**
     * @return array{ok: bool, items: array<int, mixed>, next_sync_token: string|null, status: int}
     */
    public function listChanges(UserCalendarConnection $connection, ?string $syncToken = null): array
    {
        $accessToken = $this->getValidAccessToken($connection);
        $items = [];
        $nextPageToken = null;
        $nextSyncToken = null;
        $status = 200;

        do {
            $response = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/calendar/v3/calendars/'.$connection->google_calendar_id.'/events', array_filter([
                    'singleEvents' => 'true',
                    'showDeleted' => 'true',
                    'syncToken' => $syncToken,
                    'pageToken' => $nextPageToken,
                ], fn ($value) => $value !== null));

            $status = $response->status();

            if (! $response->successful()) {
                $connection->update([
                    'status' => $response->status() === 410 ? 'error' : $connection->status,
                    'last_error_at' => now(),
                    'last_error_message' => (string) ($response->json('error.message') ?? $response->body()),
                ]);

                return [
                    'ok' => false,
                    'status' => $status,
                    'items' => [],
                    'next_sync_token' => null,
                ];
            }

            $items = array_merge($items, $response->json('items', []));
            $nextPageToken = $response->json('nextPageToken');
            $nextSyncToken = $response->json('nextSyncToken', $nextSyncToken);
        } while ($nextPageToken);

        return [
            'ok' => true,
            'status' => $status,
            'items' => $items,
            'next_sync_token' => $nextSyncToken,
        ];
    }

    public function createEvent(UserCalendarConnection $connection, array $payload): array
    {
        $response = Http::withToken($this->getValidAccessToken($connection))
            ->post('https://www.googleapis.com/calendar/v3/calendars/'.$connection->google_calendar_id.'/events', $payload);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
        ];
    }

    public function updateEvent(UserCalendarConnection $connection, string $eventId, array $payload): array
    {
        $response = Http::withToken($this->getValidAccessToken($connection))
            ->put('https://www.googleapis.com/calendar/v3/calendars/'.$connection->google_calendar_id.'/events/'.$eventId, $payload);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
        ];
    }

    public function deleteEvent(UserCalendarConnection $connection, string $eventId): array
    {
        $response = Http::withToken($this->getValidAccessToken($connection))
            ->delete('https://www.googleapis.com/calendar/v3/calendars/'.$connection->google_calendar_id.'/events/'.$eventId);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
        ];
    }

    public function getValidAccessToken(UserCalendarConnection $connection): string
    {
        if ($connection->token_expires_at && $connection->token_expires_at->isFuture()) {
            return (string) $this->tokenStore->decrypt($connection->encrypted_access_token);
        }

        return $this->refreshAccessToken($connection);
    }

    private function refreshAccessToken(UserCalendarConnection $connection): string
    {
        $refreshToken = $this->tokenStore->decrypt($connection->encrypted_refresh_token);

        if (! $refreshToken) {
            throw ValidationException::withMessages([
                'refresh_token' => 'Missing refresh token for Google Calendar connection.',
            ]);
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            $connection->update([
                'status' => 'revoked',
                'last_error_at' => now(),
                'last_error_message' => (string) ($response->json('error_description') ?? $response->json('error') ?? 'Failed to refresh Google Calendar access token.'),
            ]);

            throw ValidationException::withMessages([
                'refresh_token' => 'Failed to refresh Google Calendar access token.',
            ]);
        }

        $newAccessToken = (string) $response->json('access_token');
        $expiresIn = (int) $response->json('expires_in', 3600);

        $connection->update([
            'encrypted_access_token' => $this->tokenStore->encrypt($newAccessToken),
            'token_expires_at' => now()->addSeconds($expiresIn),
            'status' => 'connected',
            'last_error_at' => null,
            'last_error_message' => null,
        ]);

        return $newAccessToken;
    }
}
