<?php

namespace Tests\Fakes\Auth;

use App\Contracts\Auth\GoogleTokenVerifier;
use Illuminate\Validation\ValidationException;

class FakeGoogleTokenVerifier implements GoogleTokenVerifier
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(private readonly ?array $payload = null) {}

    public function verify(string $idToken): array
    {
        if ($this->payload === null || $idToken === 'invalid-token') {
            throw ValidationException::withMessages([
                'id_token' => 'The Google ID token is invalid.',
            ]);
        }

        return $this->payload;
    }
}
