<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Services\Integrations\GoogleCalendarOAuthService;
use Illuminate\Http\JsonResponse;

class GoogleCalendarDisconnectController extends Controller
{
    public function __invoke(GoogleCalendarOAuthService $oauthService): JsonResponse
    {
        $oauthService->disconnect(request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Google Calendar & Tasks disconnected successfully.',
        ]);
    }
}
