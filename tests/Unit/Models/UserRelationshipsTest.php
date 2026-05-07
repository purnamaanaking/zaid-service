<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class UserRelationshipsTest extends TestCase
{
    public function test_user_has_identities_relationship(): void
    {
        $this->assertInstanceOf(HasMany::class, (new User())->identities());
    }

    public function test_user_has_phones_relationship(): void
    {
        $this->assertInstanceOf(HasMany::class, (new User())->phones());
    }
}
