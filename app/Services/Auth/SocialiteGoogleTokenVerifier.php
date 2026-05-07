<?php

namespace App\Services\Auth;

use App\Contracts\Auth\GoogleTokenVerifier;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialiteGoogleTokenVerifier implements GoogleTokenVerifier
{
    public function verify(string $idToken): array
    {
        try {
            $googleUser = Socialite::driver('google')->userFromToken($idToken);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'id_token' => 'The Google ID token is invalid.',
            ]);
        }

        return [
            'sub' => (string) $googleUser->getId(),
            'email' => (string) $googleUser->getEmail(),
            'name' => $googleUser->getName(),
            'picture' => $googleUser->getAvatar(),
            'raw' => $googleUser->user,
        ];
    }
}
