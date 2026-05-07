<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\VerifyOtpRequest;
use App\Services\Auth\PhoneVerificationService;
use Illuminate\Http\JsonResponse;

class VerifyPhoneOtpController extends Controller
{
    public function __invoke(VerifyOtpRequest $request, PhoneVerificationService $phoneVerificationService): JsonResponse
    {
        $payload = $phoneVerificationService->verifyOtp(
            $request->user(),
            $request->string('verification_id')->toString(),
            $request->string('otp_code')->toString(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Phone verified successfully',
            'data' => $payload,
        ]);
    }
}
