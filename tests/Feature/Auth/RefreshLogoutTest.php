<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class RefreshLogoutTest extends TestCase
{
    public function test_authenticated_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test');
        $plainToken = $token->plainTextToken;

        $response = $this->withToken($plainToken)->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in']]);

        // Old token should be deleted from DB
        $this->assertNull(PersonalAccessToken::findToken($plainToken));

        // New token should work
        $newToken = $response->json('data.access_token');
        $this->assertNotNull($newToken);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test');
        $plainToken = $token->plainTextToken;

        $response = $this->withToken($plainToken)->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Token should be deleted from DB
        $this->assertNull(PersonalAccessToken::findToken($plainToken));
    }
}
