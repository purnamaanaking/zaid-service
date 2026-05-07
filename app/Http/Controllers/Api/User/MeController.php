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
            ],
        ]);
    }
}
