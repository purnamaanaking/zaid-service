<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $settings = UserSetting::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'theme' => 'light',
                'timezone' => 'Asia/Jakarta',
                'default_task_time' => '09:00:00',
                'reminder_offset_minutes' => 30,
                'reminder_enabled' => true,
            ],
        );
        $calendarConnection = $request->user()->calendarConnections()->latest('updated_at')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'theme' => $settings->theme,
                'timezone' => $settings->timezone,
                'default_task_time' => $settings->default_task_time,
                'reminder_offset_minutes' => $settings->reminder_offset_minutes,
                'reminder_enabled' => $settings->reminder_enabled,
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

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'theme' => ['sometimes', 'string', 'in:light,dark'],
            'timezone' => ['sometimes', 'string'],
            'default_task_time' => ['sometimes', 'date_format:H:i:s'],
            'reminder_offset_minutes' => ['sometimes', 'integer', 'min:0'],
            'reminder_enabled' => ['sometimes', 'boolean'],
        ]);

        $settings = UserSetting::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
        );

        $settings->update($request->only([
            'theme',
            'timezone',
            'default_task_time',
            'reminder_offset_minutes',
            'reminder_enabled',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => [
                'theme' => $settings->theme,
                'timezone' => $settings->timezone,
                'default_task_time' => $settings->default_task_time,
                'reminder_offset_minutes' => $settings->reminder_offset_minutes,
                'reminder_enabled' => $settings->reminder_enabled,
            ],
        ]);
    }
}
