<?php

namespace Tests\Feature\Integrations;

use App\Models\User;
use Tests\TestCase;

class GoogleCalendarStatusExposureTest extends TestCase
{
    public function test_me_endpoint_exposes_google_calendar_connection_summary(): void
    {
        $user = User::factory()->active()->create();
        $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'google_calendar_summary' => 'Primary Calendar',
            'encrypted_access_token' => 'enc-access',
            'encrypted_refresh_token' => 'enc-refresh',
            'token_expires_at' => now()->addHour(),
            'status' => 'connected',
            'last_synced_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('data.integrations.google_calendar.connected', true)
            ->assertJsonPath('data.integrations.google_calendar.google_calendar_id', 'primary')
            ->assertJsonPath('data.integrations.google_calendar.status', 'connected');
    }

    public function test_settings_endpoint_exposes_google_calendar_connection_summary(): void
    {
        $user = User::factory()->active()->create();
        $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'google_calendar_summary' => 'Primary Calendar',
            'encrypted_access_token' => 'enc-access',
            'encrypted_refresh_token' => 'enc-refresh',
            'token_expires_at' => now()->addHour(),
            'status' => 'connected',
            'last_synced_at' => now(),
            'last_error_message' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/settings');

        $response->assertOk()
            ->assertJsonPath('data.integrations.google_calendar.connected', true)
            ->assertJsonPath('data.integrations.google_calendar.google_calendar_summary', 'Primary Calendar')
            ->assertJsonPath('data.integrations.google_calendar.last_error_message', null);
    }
}
