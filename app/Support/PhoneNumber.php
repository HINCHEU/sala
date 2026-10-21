<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalise a Cambodian phone number to +855 form: "012 345 678", "+855 12 345 678"
     * and "85512345678" all become "+85512345678". Returns null when it is not a Cambodian number.
     */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input);

        if (str_starts_with($digits, '855')) {
            $digits = substr($digits, 3);
        }

        $digits = ltrim($digits, '0');

        // Cambodian national numbers are 8 or 9 digits without the leading 0.
        return preg_match('/^[1-9]\d{7,8}$/', $digits) ? '+855'.$digits : null;
    }
}
