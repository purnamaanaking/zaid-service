<?php

namespace Tests\Feature\Auth;

use App\Contracts\Auth\GoogleTokenVerifier;
use App\Enums\UserStatus;
use App\Models\User;
use Tests\Fakes\Auth\FakeGoogleTokenVerifier;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    public function test_new_google_login_creates_provisional_user(): void
    {
        $this->app->bind(GoogleTokenVerifier::class, fn () => new FakeGoogleTokenVerifier([
            'sub' => 'google-subject-1',
            'email' => 'new-user@example.com',
            'name' => 'New User',
            'picture' => 'https://example.com/avatar.png',
            'raw' => ['sub' => 'google-subject-1'],
        ]));

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'valid-token',
            'device' => [
                'device_name' => 'iPhone 15',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'new-user@example.com')
            ->assertJsonPath('data.user.status', UserStatus::Provisional->value)
            ->assertJsonPath('data.onboarding.next_step', 'phone_input');
    }

    public function test_existing_verified_user_returns_dashboard_step(): void
    {
        $user = User::factory()->active()->create([
            'google_subject' => 'google-subject-2',
            'email' => 'verified@example.com',
        ]);

        \App\Models\UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+628111111111',
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => now(),
            'linked_for_whatsapp_at' => now(),
        ]);

        $this->app->bind(GoogleTokenVerifier::class, fn () => new FakeGoogleTokenVerifier([
            'sub' => 'google-subject-2',
            'email' => 'verified@example.com',
            'name' => 'Verified User',
            'picture' => null,
            'raw' => ['sub' => 'google-subject-2'],
        ]));

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'valid-token',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.onboarding.next_step', 'dashboard');
    }

    public function test_invalid_google_token_is_rejected(): void
    {
        $this->app->bind(GoogleTokenVerifier::class, fn () => new FakeGoogleTokenVerifier());

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'invalid-token',
        ]);

        $response->assertStatus(422);
    }
}
