<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\ResendOtpRequest;
use App\Services\Auth\PhoneVerificationService;
use Illuminate\Http\JsonResponse;

class ResendOtpController extends Controller
{
    public function __invoke(ResendOtpRequest $request, PhoneVerificationService $phoneVerificationService): JsonResponse
    {
        $payload = $phoneVerificationService->resendOtp(
            $request->user(),
            $request->string('phone_number')->toString(),
        );

        return response()->json([
            'success' => true,
            'message' => 'OTP resent successfully',
            'data' => $payload,
        ]);
    }
}
