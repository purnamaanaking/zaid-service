<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleAuthRequest;
use App\Services\Auth\GoogleAuthService;
use Illuminate\Http\JsonResponse;

class GoogleAuthController extends Controller
{
    public function __invoke(GoogleAuthRequest $request, GoogleAuthService $googleAuthService): JsonResponse
    {
        $payload = $googleAuthService->authenticate(
            $request->string('id_token')->toString(),
            $request->input('device', []),
        );

        return response()->json([
            'success' => true,
            'message' => 'Authenticated successfully',
            'data' => $payload,
        ]);
    }
}
