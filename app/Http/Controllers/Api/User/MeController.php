<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $user = request()->user();
        $primaryPhone = $user->phones()->where('is_primary', true)->first();
        $calendarConnection = $user->calendarConnections()->latest('updated_at')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'avatar_url' => $user->avatar_url,
                'phone_number' => $primaryPhone?->phone_e164,
                'phone_verified' => $user->phone_verified_at !== null,
                'status' => $user->status,
                'settings' => [
                    'default_task_time' => '09:00',
                    'theme' => 'light',
                    'timezone' => 'Asia/Jakarta',
                ],
                'integrations' => [
                    'google_calendar' => [
                        'connected' => $calendarConnection?->status === 'connected',
                        'google_calendar_id' => $calendarConnection?->google_calendar_id,
                        'google_calendar_summary' => $calendarConnection?->google_calendar_summary,
                        'status' => $calendarConnection?->status,
                        'last_synced_at' => $calendarConnection?->last_synced_at,
                        'last_error_message' => $calendarConnection?->last_error_message,
                    ],
                ],
            ],
        ]);
    }
}
