<?php

namespace App\Services\Integrations;

use App\Models\UserCalendarConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleCalendarWatchService
{
    public function __construct(
        private readonly GoogleCalendarApiService $apiService,
    ) {}

    public function registerWatch(UserCalendarConnection $connection): bool
    {
        $channelId = Str::uuid()->toString();
        $webhookUrl = rtrim(config('app.url'), '/') . '/api/v1/webhooks/google-calendar';

        // Google watch channels expire max 7 days, we request ~6 days
        $expiration = now()->addDays(6)->getTimestampMs();

        try {
            $accessToken = $this->apiService->getValidAccessToken($connection);

            $response = Http::withToken($accessToken)
                ->post('https://www.googleapis.com/calendar/v3/calendars/' . $connection->google_calendar_id . '/events/watch', [
                    'id' => $channelId,
                    'type' => 'web_hook',
                    'address' => $webhookUrl,
                    'expiration' => $expiration,
                ]);

            if (! $response->successful()) {
                Log::error('Google Calendar watch registration failed.', [
                    'connection_id' => $connection->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $connection->update([
                'watch_channel_id' => $response->json('id', $channelId),
                'watch_resource_id' => $response->json('resourceId'),
                'watch_expiry' => now()->addMilliseconds($response->json('expiration', $expiration) - now()->getTimestampMs()),
            ]);

            Log::info('Google Calendar watch registered.', [
                'connection_id' => $connection->id,
                'channel_id' => $connection->watch_channel_id,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Google Calendar watch registration error.', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function stopWatch(UserCalendarConnection $connection): void
    {
        if (! $connection->watch_channel_id || ! $connection->watch_resource_id) {
            return;
        }

        try {
            $accessToken = $this->apiService->getValidAccessToken($connection);

            Http::withToken($accessToken)
                ->post('https://www.googleapis.com/calendar/v3/channels/stop', [
                    'id' => $connection->watch_channel_id,
                    'resourceId' => $connection->watch_resource_id,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to stop Google Calendar watch.', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
        }

        $connection->update([
            'watch_channel_id' => null,
            'watch_resource_id' => null,
            'watch_expiry' => null,
        ]);
    }

    public function renewExpiringWatches(): int
    {
        $renewed = 0;

        // Renew watches expiring within 1 day
        UserCalendarConnection::query()
            ->where('provider', 'google_calendar')
            ->where('status', 'connected')
            ->where(function ($q) {
                $q->whereNull('watch_expiry')
                    ->orWhere('watch_expiry', '<', now()->addDay());
            })
            ->each(function (UserCalendarConnection $connection) use (&$renewed) {
                $this->stopWatch($connection);
                if ($this->registerWatch($connection)) {
                    $renewed++;
                }
            });

        return $renewed;
    }
}
