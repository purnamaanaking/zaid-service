<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GoogleCalendarStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $connection = request()->user()->calendarConnections()->latest('updated_at')->first();

        return response()->json([
            'success' => true,
            'message' => 'Google Calendar & Tasks integration status fetched.',
            'data' => [
                'connected' => $connection?->status === 'connected',
                'connection' => $connection ? [
                    'id' => $connection->id,
                    'provider' => $connection->provider,
                    'google_calendar_id' => $connection->google_calendar_id,
                    'google_calendar_summary' => $connection->google_calendar_summary,
                    'status' => $connection->status,
                    'last_synced_at' => $connection->last_synced_at,
                    'last_error_at' => $connection->last_error_at,
                    'last_error_message' => $connection->last_error_message,
                ] : null,
                'includes' => [
                    'google_calendar' => true,
                    'google_tasks' => true,
                ],
            ],
        ]);
    }
}
