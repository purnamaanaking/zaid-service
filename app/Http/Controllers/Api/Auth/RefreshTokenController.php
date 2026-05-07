<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefreshTokenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();

        $newToken = $user->createToken($request->header('User-Agent', 'mobile-app'));

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'access_token' => $newToken->plainTextToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ],
        ]);
    }
}
