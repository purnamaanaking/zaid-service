<?php

namespace App\Contracts\Auth;

interface GoogleTokenVerifier
{
    /**
     * @return array{sub: string, email: string, name?: string|null, picture?: string|null, raw?: array|null}
     */
    public function verify(string $idToken): array;
}
