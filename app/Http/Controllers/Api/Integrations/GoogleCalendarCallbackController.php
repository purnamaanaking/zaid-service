<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Services\Integrations\GoogleCalendarOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleCalendarCallbackController extends Controller
{
    public function __invoke(Request $request, GoogleCalendarOAuthService $oauthService): RedirectResponse
    {
        return $oauthService->handleCallbackFromState(
            $request->string('code')->toString(),
            $request->string('state')->toString(),
        );
    }
}
