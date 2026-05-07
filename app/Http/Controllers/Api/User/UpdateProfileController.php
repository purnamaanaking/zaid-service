<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateProfileController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
        ]);

        $user = $request->user();
        $user->update($request->only(['full_name', 'avatar_url']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'avatar_url' => $user->avatar_url,
            ],
        ]);
    }
}
