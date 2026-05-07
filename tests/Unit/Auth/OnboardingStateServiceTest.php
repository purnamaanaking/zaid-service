<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use App\Models\UserPhone;
use App\Services\Auth\OnboardingStateService;
use Tests\TestCase;

class OnboardingStateServiceTest extends TestCase
{
    public function test_user_without_phone_must_input_phone(): void
    {
        $user = User::factory()->make();

        $state = (new OnboardingStateService())->resolve($user);

        $this->assertTrue($state['required']);
        $this->assertSame('phone_input', $state['next_step']);
    }

    public function test_user_with_unverified_phone_must_verify_otp(): void
    {
        $user = User::factory()->make();
        $user->setRelation('phones', collect([
            new UserPhone(['is_verified' => false]),
        ]));

        $state = (new OnboardingStateService())->resolve($user);

        $this->assertTrue($state['required']);
        $this->assertSame('verify_otp', $state['next_step']);
    }
}
