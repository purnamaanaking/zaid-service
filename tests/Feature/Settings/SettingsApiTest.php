<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    public function test_user_can_get_settings(): void
    {
        $user = User::factory()->active()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/settings');

        $response->assertOk()
            ->assertJsonPath('data.theme', 'light')
            ->assertJsonPath('data.timezone', 'Asia/Jakarta');
    }

    public function test_user_can_update_settings(): void
    {
        $user = User::factory()->active()->create();

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/settings', [
            'theme' => 'dark',
            'reminder_offset_minutes' => 15,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.theme', 'dark')
            ->assertJsonPath('data.reminder_offset_minutes', 15);
    }
}
