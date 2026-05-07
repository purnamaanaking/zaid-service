<?php

namespace App\Services\Auth;

use App\Enums\UserStatus;
use App\Jobs\Auth\SendOtpJob;
use App\Models\OtpAttempt;
use App\Models\PhoneVerification;
use App\Models\User;
use App\Models\UserPhone;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PhoneVerificationService
{
    /**
     * @return array{phone_number: string, verification_id: string, expires_in_seconds: int, next_step: string}
     */
    public function submitPhone(User $user, string $phoneNumber, ?string $countryCode = 'ID'): array
    {
        $normalized = PhoneNumber::normalize($phoneNumber);

        $existingPhone = UserPhone::query()
            ->where('phone_e164', $normalized)
            ->where('user_id', '!=', $user->id)
            ->first();

        if ($existingPhone !== null && $existingPhone->is_verified) {
            throw ValidationException::withMessages([
                'phone_number' => 'Phone number is already linked to another account.',
            ]);
        }

        return DB::transaction(function () use ($countryCode, $normalized, $phoneNumber, $user): array {
            UserPhone::query()->where('user_id', $user->id)->update(['is_primary' => false]);

            $userPhone = UserPhone::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'phone_e164' => $normalized,
                ],
                [
                    'phone_local' => $phoneNumber,
                    'country_code' => $countryCode,
                    'is_primary' => true,
                    'is_verified' => false,
                    'verified_at' => null,
                    'linked_for_whatsapp_at' => null,
                ],
            );

            PhoneVerification::query()
                ->where('user_phone_id', $userPhone->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            $otpCode = (string) random_int(100000, 999999);
            $verification = PhoneVerification::query()->create([
                'user_phone_id' => $userPhone->id,
                'otp_code_hash' => Hash::make($otpCode),
                'channel' => 'email',
                'status' => 'pending',
                'expires_at' => now()->addMinutes(5),
                'attempt_count' => 0,
            ]);

            OtpAttempt::query()->create([
                'user_phone_id' => $userPhone->id,
                'phone_verification_id' => $verification->id,
                'attempt_type' => 'send_otp',
                'status' => 'success',
                'created_at' => now(),
            ]);

            SendOtpJob::dispatch($user->email, $otpCode, $verification->id, $user->full_name ?? 'User');

            return [
                'phone_number' => $normalized,
                'verification_id' => $verification->id,
                'expires_in_seconds' => 300,
                'next_step' => 'verify_otp',
            ];
        });
    }

    /**
     * @return array{user: array<string, mixed>, onboarding: array<string, mixed>}
     */
    public function verifyOtp(User $user, string $verificationId, string $otpCode): array
    {
        /** @var PhoneVerification $verification */
        $verification = PhoneVerification::query()
            ->whereKey($verificationId)
            ->firstOrFail();

        $userPhone = $verification->userPhone;

        if ($userPhone->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'verification_id' => 'Verification does not belong to the authenticated user.',
            ]);
        }

        if ($verification->status !== 'pending') {
            throw ValidationException::withMessages([
                'verification_id' => 'Verification is no longer active.',
            ]);
        }

        if ($verification->expires_at->isPast()) {
            $verification->update(['status' => 'expired']);

            throw ValidationException::withMessages([
                'otp_code' => 'OTP has expired.',
            ]);
        }

        $verification->increment('attempt_count');

        if (! Hash::check($otpCode, $verification->otp_code_hash)) {
            OtpAttempt::query()->create([
                'user_phone_id' => $userPhone->id,
                'phone_verification_id' => $verification->id,
                'attempt_type' => 'verify_otp',
                'status' => 'invalid_code',
                'created_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'otp_code' => 'OTP is invalid.',
            ]);
        }

        DB::transaction(function () use ($user, $userPhone, $verification): void {
            $verification->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            $userPhone->update([
                'is_verified' => true,
                'verified_at' => now(),
                'linked_for_whatsapp_at' => now(),
            ]);

            $user->update([
                'status' => UserStatus::Active->value,
                'phone_verified_at' => now(),
                'onboarded_at' => now(),
                'last_active_at' => now(),
            ]);

            OtpAttempt::query()->create([
                'user_phone_id' => $userPhone->id,
                'phone_verification_id' => $verification->id,
                'attempt_type' => 'verify_otp',
                'status' => 'success',
                'created_at' => now(),
            ]);
        });

        return [
            'user' => [
                'id' => $user->id,
                'status' => UserStatus::Active->value,
                'phone_verified' => true,
                'phone_number' => $userPhone->phone_e164,
            ],
            'onboarding' => [
                'completed' => true,
                'next_step' => 'dashboard',
            ],
        ];
    }

    /**
     * @return array{phone_number: string, verification_id: string, expires_in_seconds: int, next_step: string}
     */
    public function resendOtp(User $user, string $phoneNumber): array
    {
        return $this->submitPhone($user, $phoneNumber);
    }
}
