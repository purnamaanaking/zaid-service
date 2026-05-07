<?php

namespace App\Support;

use InvalidArgumentException;

class PhoneNumber
{
    public static function normalize(string $input, string $defaultCountryCode = '62'): string
    {
        $digits = preg_replace('/\D+/', '', $input ?? '');

        if ($digits === null || $digits === '') {
            throw new InvalidArgumentException('Phone number is required.');
        }

        if (str_starts_with($digits, '0')) {
            $digits = $defaultCountryCode.substr($digits, 1);
        }

        if (! str_starts_with($digits, $defaultCountryCode) && ! str_starts_with($digits, '62')) {
            $digits = $defaultCountryCode.$digits;
        }

        return '+'.$digits;
    }
}
