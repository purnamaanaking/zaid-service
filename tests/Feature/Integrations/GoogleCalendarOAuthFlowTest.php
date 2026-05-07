<?php

namespace Tests\Feature\Integrations;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleCalendarOAuthFlowTest extends TestCase
{
    public function test_authenticated_user_can_get_google_calendar_connect_url(): void
    {
        $user = User::factory()->active()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/integrations/google-calendar/connect');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.provider', 'google_calendar');

        $this->assertStringContainsString('accounts.google.com', (string) $response->json('data.redirect_url'));
        $this->assertStringContainsString('calendar', (string) $response->json('data.redirect_url'));
    }

    public function test_callback_stores_calendar_connection_and_tokens(): void
    {
        config([
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.calendar_redirect' => 'http://localhost/api/v1/integrations/google-calendar/callback',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
                'refresh_token' => 'google-refresh-token',
                'expires_in' => 3600,
                'scope' => 'https://www.googleapis.com/auth/calendar',
                'token_type' => 'Bearer',
            ], 200),
            'https://www.googleapis.com/oauth2/v2/userinfo' => Http::response([
                'id' => 'google-subject-123',
                'email' => 'calendar@example.com',
            ], 200),
            'https://www.googleapis.com/calendar/v3/users/me/calendarList/primary' => Http::response([
                'id' => 'primary',
                'summary' => 'Primary Calendar',
            ], 200),
        ]);

        $user = User::factory()->active()->create([
            'google_subject' => 'google-subject-123',
            'email' => 'calendar@example.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')->get('/api/v1/integrations/google-calendar/callback?code=auth-code-123&state='.$user->id);

        $response->assertRedirect('/settings?google_calendar=connected');

        $this->assertDatabaseHas('user_calendar_connections', [
            'user_id' => $user->id,
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'status' => 'connected',
        ]);
    }

    public function test_status_endpoint_returns_connected_state(): void
    {
        $user = User::factory()->active()->create();

        $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'google_calendar_summary' => 'Primary Calendar',
            'encrypted_access_token' => 'enc-access',
            'encrypted_refresh_token' => 'enc-refresh',
            'token_expires_at' => now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/calendar'],
            'status' => 'connected',
            'last_synced_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/integrations/google-calendar/status');

        $response->assertOk()
            ->assertJsonPath('data.connected', true)
            ->assertJsonPath('data.connection.google_calendar_id', 'primary')
            ->assertJsonPath('data.connection.status', 'connected');
    }

    public function test_disconnect_endpoint_clears_connection_state(): void
    {
        $user = User::factory()->active()->create();

        $connection = $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'google_calendar_summary' => 'Primary Calendar',
            'encrypted_access_token' => 'enc-access',
            'encrypted_refresh_token' => 'enc-refresh',
            'token_expires_at' => now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/calendar'],
            'status' => 'connected',
            'last_synced_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/integrations/google-calendar');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $connection->refresh();

        $this->assertSame('disconnected', $connection->status);
        $this->assertNull($connection->encrypted_access_token);
        $this->assertNull($connection->encrypted_refresh_token);
        $this->assertNull($connection->sync_token);
    }
}
