<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IdentitySchemaTest extends TestCase
{
    public function test_identity_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('user_identities'));
        $this->assertTrue(Schema::hasTable('user_phones'));
        $this->assertTrue(Schema::hasTable('phone_verifications'));
        $this->assertTrue(Schema::hasTable('otp_attempts'));
    }

    public function test_users_table_contains_zaid_identity_columns(): void
    {
        foreach (['google_subject', 'email', 'full_name', 'avatar_url', 'status', 'phone_verified_at', 'onboarded_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), $column);
        }
    }
}
