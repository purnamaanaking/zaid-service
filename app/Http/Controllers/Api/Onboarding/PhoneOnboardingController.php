<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\SubmitPhoneRequest;
use App\Services\Auth\PhoneVerificationService;
use Illuminate\Http\JsonResponse;

class PhoneOnboardingController extends Controller
{
    public function __invoke(SubmitPhoneRequest $request, PhoneVerificationService $phoneVerificationService): JsonResponse
    {
        $payload = $phoneVerificationService->submitPhone(
            $request->user(),
            $request->string('phone_number')->toString(),
            $request->input('country_code', 'ID'),
        );

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data' => $payload,
        ]);
    }
}
