<?php

namespace App\Services\Auth;

use App\Contracts\Auth\GoogleTokenVerifier;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Support\Facades\DB;

class GoogleAuthService
{
    public function __construct(
        private readonly GoogleTokenVerifier $googleTokenVerifier,
        private readonly OnboardingStateService $onboardingStateService,
    ) {}

    /**
     * @param  array{platform?: string|null, device_id?: string|null, device_name?: string|null}  $device
     * @return array{access_token: string, token_type: string, expires_in: int, user: array<string, mixed>, onboarding: array<string, mixed>}
     */
    public function authenticate(string $idToken, array $device = []): array
    {
        $payload = $this->googleTokenVerifier->verify($idToken);

        /** @var User $user */
        $user = DB::transaction(function () use ($payload): User {
            $user = User::query()->firstOrNew([
                'google_subject' => $payload['sub'],
            ]);

            $user->fill([
                'email' => $payload['email'],
                'full_name' => $payload['name'] ?? null,
                'avatar_url' => $payload['picture'] ?? null,
                'status' => $user->exists ? $user->status : UserStatus::Provisional->value,
                'last_active_at' => now(),
            ]);

            $user->save();

            UserIdentity::query()->updateOrCreate(
                [
                    'provider' => 'google',
                    'provider_subject' => $payload['sub'],
                ],
                [
                    'user_id' => $user->id,
                    'provider_email' => $payload['email'],
                    'provider_payload' => $payload['raw'] ?? $payload,
                ],
            );

            return $user->fresh();
        });

        $token = $user->createToken($device['device_name'] ?? 'mobile-app');
        $onboarding = $this->onboardingStateService->resolve($user);

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'avatar_url' => $user->avatar_url,
                'status' => $user->status,
                'phone_verified' => $onboarding['phone_verified'],
            ],
            'onboarding' => $onboarding,
        ];
    }
}
