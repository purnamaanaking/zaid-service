<?php

namespace Tests\Feature\Onboarding;

use App\Enums\UserStatus;
use App\Models\PhoneVerification;
use App\Models\User;
use App\Models\UserPhone;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VerifyOtpTest extends TestCase
{
    public function test_valid_otp_activates_user(): void
    {
        $user = User::factory()->create();
        $phone = UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+628123456789',
            'phone_local' => '08123456789',
            'country_code' => 'ID',
            'is_primary' => true,
            'is_verified' => false,
        ]);

        $verification = PhoneVerification::query()->create([
            'user_phone_id' => $phone->id,
            'otp_code_hash' => Hash::make('123456'),
            'status' => 'pending',
            'channel' => 'whatsapp',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/onboarding/phone/verify', [
            'verification_id' => $verification->id,
            'otp_code' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.status', UserStatus::Active->value)
            ->assertJsonPath('data.onboarding.next_step', 'dashboard');
    }
}
