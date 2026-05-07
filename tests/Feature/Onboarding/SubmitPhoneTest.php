<?php

namespace Tests\Feature\Onboarding;

use App\Jobs\Auth\SendOtpJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SubmitPhoneTest extends TestCase
{
    public function test_authenticated_user_can_submit_phone_number(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/onboarding/phone', [
            'phone_number' => '08123456789',
            'country_code' => 'ID',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.phone_number', '+628123456789')
            ->assertJsonPath('data.next_step', 'verify_otp');

        Queue::assertPushed(SendOtpJob::class);
    }
}
