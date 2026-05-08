<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Jobs\Calendar\SyncGoogleCalendarConnectionJob;
use App\Models\UserCalendarConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class GoogleCalendarWebhookController
{
    public function __invoke(Request $request): Response
    {
        $channelId = $request->header('X-Goog-Channel-ID');
        $resourceState = $request->header('X-Goog-Resource-State');

        if (! $channelId) {
            return response()->noContent(400);
        }

        // Google sends 'sync' on initial registration — acknowledge but don't process
        if ($resourceState === 'sync') {
            Log::info('Google Calendar watch sync confirmation.', ['channel_id' => $channelId]);

            return response()->noContent();
        }

        $connection = UserCalendarConnection::query()
            ->where('watch_channel_id', $channelId)
            ->where('status', 'connected')
            ->first();

        if (! $connection) {
            Log::warning('Google Calendar webhook for unknown channel.', ['channel_id' => $channelId]);

            return response()->noContent();
        }

        Log::info('Google Calendar push notification received.', [
            'connection_id' => $connection->id,
            'resource_state' => $resourceState,
        ]);

        SyncGoogleCalendarConnectionJob::dispatch($connection->id);

        return response()->noContent();
    }
}
