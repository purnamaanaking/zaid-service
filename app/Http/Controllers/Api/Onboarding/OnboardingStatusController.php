<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Controller;
use App\Services\Auth\OnboardingStateService;
use Illuminate\Http\JsonResponse;

class OnboardingStatusController extends Controller
{
    public function __invoke(OnboardingStateService $onboardingStateService): JsonResponse
    {
        $user = request()->user();
        $state = $onboardingStateService->resolve($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user_status' => $user->status,
                'phone_verified' => $state['phone_verified'],
                'required' => $state['required'],
                'next_step' => $state['next_step'],
            ],
        ]);
    }
}
