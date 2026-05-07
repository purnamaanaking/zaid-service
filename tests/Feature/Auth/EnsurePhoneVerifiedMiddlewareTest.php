<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class EnsurePhoneVerifiedMiddlewareTest extends TestCase
{
    public function test_provisional_user_is_blocked_from_tasks(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks');

        $response->assertForbidden()
            ->assertJsonPath('error.code', 'PHONE_NOT_VERIFIED');
    }

    public function test_active_verified_user_can_access_tasks(): void
    {
        $user = User::factory()->active()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks');

        $response->assertOk();
    }
}
