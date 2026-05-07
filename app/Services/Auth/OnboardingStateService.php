<?php

namespace App\Services\Auth;

use App\Enums\UserStatus;
use App\Models\User;

class OnboardingStateService
{
    /**
     * @return array{required: bool, next_step: string, phone_verified: bool}
     */
    public function resolve(User $user): array
    {
        $primaryPhone = $user->relationLoaded('phones')
            ? $user->phones->first()
            : $user->phones()->latest('created_at')->first();

        if ($primaryPhone === null) {
            return [
                'required' => true,
                'next_step' => 'phone_input',
                'phone_verified' => false,
            ];
        }

        if (! $primaryPhone->is_verified || $user->status !== UserStatus::Active->value) {
            return [
                'required' => true,
                'next_step' => 'verify_otp',
                'phone_verified' => false,
            ];
        }

        return [
            'required' => false,
            'next_step' => 'dashboard',
            'phone_verified' => true,
        ];
    }
}
