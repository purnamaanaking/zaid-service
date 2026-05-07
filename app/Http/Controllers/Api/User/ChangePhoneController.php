<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\SubmitPhoneRequest;
use App\Services\Auth\PhoneVerificationService;
use Illuminate\Http\JsonResponse;

class ChangePhoneController extends Controller
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
            'message' => 'OTP sent to new phone number',
            'data' => $payload,
        ]);
    }
}
