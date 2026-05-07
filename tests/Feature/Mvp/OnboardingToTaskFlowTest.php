<?php

namespace Tests\Feature\Mvp;

use App\Contracts\Auth\GoogleTokenVerifier;
use App\Contracts\Prompt\PromptParser;
use Tests\Fakes\Auth\FakeGoogleTokenVerifier;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class OnboardingToTaskFlowTest extends TestCase
{
    public function test_full_onboarding_to_task_creation_flow(): void
    {
        // Step 1: Google auth
        $this->app->bind(GoogleTokenVerifier::class, fn () => new FakeGoogleTokenVerifier([
            'sub' => 'mvp-test-subject',
            'email' => 'mvp@example.com',
            'name' => 'MVP User',
            'picture' => null,
            'raw' => ['sub' => 'mvp-test-subject'],
        ]));

        $authResponse = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'valid-token',
        ]);

        $authResponse->assertOk();
        $accessToken = $authResponse->json('data.access_token');
        $this->assertNotNull($accessToken);

        // Step 2: Check onboarding status
        $statusResponse = $this->withToken($accessToken)->getJson('/api/v1/onboarding/status');
        $statusResponse->assertOk()
            ->assertJsonPath('data.next_step', 'phone_input');

        // Step 3: Submit phone
        $phoneResponse = $this->withToken($accessToken)->postJson('/api/v1/onboarding/phone', [
            'phone_number' => '08123456789',
        ]);

        $phoneResponse->assertOk();
        $verificationId = $phoneResponse->json('data.verification_id');
        $this->assertNotNull($verificationId);
    }
}
