<?php

namespace Tests\Feature\Foundation;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiErrorFormatTest extends TestCase
{
    public function test_unauthenticated_returns_standard_json_envelope(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_not_found_returns_standard_json_envelope(): void
    {
        $user = User::factory()->active()->create();
        $fakeUuid = (string) Str::uuid();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/tasks/{$fakeUuid}");

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_validation_error_returns_standard_json_envelope(): void
    {
        $response = $this->postJson('/api/v1/auth/google', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }
}
