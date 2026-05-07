<?php

namespace Tests\Unit\Integrations;

use App\Support\Security\EncryptedTokenStore;
use Tests\TestCase;

class EncryptedTokenStoreTest extends TestCase
{
    public function test_it_encrypts_plaintext_tokens_before_storage(): void
    {
        $store = new EncryptedTokenStore();

        $encrypted = $store->encrypt('refresh-token-123');

        $this->assertNotSame('refresh-token-123', $encrypted);
        $this->assertIsString($encrypted);
        $this->assertNotEmpty($encrypted);
    }

    public function test_it_decrypts_tokens_for_runtime_use(): void
    {
        $store = new EncryptedTokenStore();

        $encrypted = $store->encrypt('refresh-token-123');

        $this->assertSame('refresh-token-123', $store->decrypt($encrypted));
    }

    public function test_it_returns_null_for_empty_values(): void
    {
        $store = new EncryptedTokenStore();

        $this->assertNull($store->encrypt(null));
        $this->assertNull($store->decrypt(null));
        $this->assertNull($store->decrypt(''));
    }
}
