<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Services\Integrations\GoogleCalendarOAuthService;
use Illuminate\Http\JsonResponse;

class GoogleCalendarConnectController extends Controller
{
    public function __invoke(GoogleCalendarOAuthService $oauthService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Google Calendar & Tasks connect URL generated.',
            'data' => [
                'provider' => 'google_calendar',
                'redirect_url' => $oauthService->buildConnectUrl(request()->user()),
            ],
        ]);
    }
}
