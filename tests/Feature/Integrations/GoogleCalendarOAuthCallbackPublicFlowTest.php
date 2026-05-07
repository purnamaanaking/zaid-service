<?php

namespace Tests\Feature\Integrations;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleCalendarOAuthCallbackPublicFlowTest extends TestCase
{
    public function test_public_callback_accepts_signed_state_without_sanctum_token(): void
    {
        config([
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.calendar_redirect' => 'http://localhost/api/v1/integrations/google-calendar/callback',
            'app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
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

        $state = app(\App\Services\Integrations\GoogleCalendarOAuthService::class)->buildSignedState($user);

        $response = $this->get('/api/v1/integrations/google-calendar/callback?code=auth-code-123&state='.urlencode($state));

        $response->assertRedirect('/settings?google_calendar=connected');

        $this->assertDatabaseHas('user_calendar_connections', [
            'user_id' => $user->id,
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'status' => 'connected',
        ]);
    }
}
