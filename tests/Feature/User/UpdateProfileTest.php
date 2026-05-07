<?php

namespace Tests\Feature\User;

use App\Models\User;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    public function test_user_can_update_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/me', [
            'full_name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.full_name', 'New Name');
    }
}
