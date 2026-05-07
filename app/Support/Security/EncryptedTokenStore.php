<?php

namespace App\Support\Security;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class EncryptedTokenStore
{
    public function encrypt(?string $token): ?string
    {
        if ($token === null || $token === '') {
            return null;
        }

        return Crypt::encryptString($token);
    }

    public function decrypt(?string $encryptedToken): ?string
    {
        if ($encryptedToken === null || $encryptedToken === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encryptedToken);
        } catch (DecryptException) {
            return null;
        }
    }
}
