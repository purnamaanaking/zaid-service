<?php

namespace App\Services\Integrations;

use App\Models\User;
use App\Support\Security\EncryptedTokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class GoogleCalendarOAuthService
{
    public function __construct(
        private readonly EncryptedTokenStore $tokenStore,
    ) {}

    public function buildConnectUrl(User $user): string
    {
        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.calendar_redirect'),
            'response_type' => 'code',
            'scope' => implode(' ', config('services.google.calendar_scopes', [])),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $this->buildSignedState($user),
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.$query;
    }

    public function buildSignedState(User $user): string
    {
        return Crypt::encryptString(json_encode([
            'user_id' => (string) $user->id,
            'issued_at' => now()->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    public function resolveUserFromState(string $state): User
    {
        try {
            $payload = json_decode(Crypt::decryptString($state), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'state' => 'Invalid OAuth state.',
            ]);
        }

        $userId = $payload['user_id'] ?? null;

        if (! is_string($userId) || $userId === '') {
            throw ValidationException::withMessages([
                'state' => 'Invalid OAuth state.',
            ]);
        }

        return User::query()->findOrFail($userId);
    }

    public function handleCallbackFromState(string $code, string $state): RedirectResponse
    {
        return $this->handleCallback(
            $this->resolveUserFromState($state),
            $code,
            $state,
        );
    }

    public function handleCallback(User $user, string $code, string $state): RedirectResponse
    {
        $resolvedUser = $this->resolveUserFromState($state);

        if ((string) $resolvedUser->id !== (string) $user->id) {
            throw ValidationException::withMessages([
                'state' => 'Invalid OAuth state.',
            ]);
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.calendar_redirect'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $tokenResponse->successful()) {
            throw ValidationException::withMessages([
                'code' => 'Failed to exchange authorization code.',
            ]);
        }

        $accessToken = $tokenResponse->json('access_token');
        $refreshToken = $tokenResponse->json('refresh_token');
        $expiresIn = (int) $tokenResponse->json('expires_in', 0);
        $scopeString = (string) $tokenResponse->json('scope', '');

        $userInfo = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');
        if (! $userInfo->successful() || $userInfo->json('id') !== $user->google_subject) {
            throw ValidationException::withMessages([
                'code' => 'Google account mismatch for calendar connection.',
            ]);
        }

        $primaryCalendarId = (string) config('services.google.calendar_primary_id', 'primary');
        $calendarInfo = Http::withToken($accessToken)->get('https://www.googleapis.com/calendar/v3/users/me/calendarList/'.$primaryCalendarId);
        if (! $calendarInfo->successful()) {
            throw ValidationException::withMessages([
                'calendar' => 'Unable to fetch Google Calendar details.',
            ]);
        }

        $connection = $user->calendarConnections()->updateOrCreate(
            [
                'provider' => 'google_calendar',
                'google_calendar_id' => $calendarInfo->json('id', $primaryCalendarId),
            ],
            [
                'google_calendar_summary' => $calendarInfo->json('summary'),
                'encrypted_access_token' => $this->tokenStore->encrypt($accessToken),
                'encrypted_refresh_token' => $refreshToken ? $this->tokenStore->encrypt($refreshToken) : null,
                'token_expires_at' => $expiresIn > 0 ? now()->addSeconds($expiresIn) : null,
                'scopes' => array_values(array_filter(array_map('trim', explode(' ', $scopeString)))),
                'status' => 'connected',
                'last_error_at' => null,
                'last_error_message' => null,
            ],
        );

        if (! $refreshToken && $connection->encrypted_refresh_token === null) {
            throw ValidationException::withMessages([
                'refresh_token' => 'Google did not return a refresh token. Reconnect with consent again.',
            ]);
        }

        return redirect('/settings?google_calendar=connected');
    }

    public function disconnect(User $user): void
    {
        $user->calendarConnections()->each(function ($connection): void {
            $connection->update([
                'encrypted_access_token' => null,
                'encrypted_refresh_token' => null,
                'token_expires_at' => null,
                'sync_token' => null,
                'status' => 'disconnected',
                'last_error_at' => null,
                'last_error_message' => null,
            ]);
        });
    }
}
