<?php

namespace Tests\Unit\Support;

use App\Support\PhoneNumber;
use InvalidArgumentException;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_normalizes_indonesian_local_number_to_e164(): void
    {
        $this->assertSame('+628123456789', PhoneNumber::normalize('08123456789'));
    }

    public function test_throws_for_empty_phone_number(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PhoneNumber::normalize('');
    }
}
