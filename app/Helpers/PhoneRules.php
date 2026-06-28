<?php

namespace App\Helpers;

class PhoneRules
{
    public static function isValidIsraeliPhone(string $phone): bool
    {
        if ($phone === '') {
            return true;
        }

        $cleaned = preg_replace('/[\s\-\(\)]/', '', trim($phone));

        if (preg_match('/^\+972[0-9]{9}$/', $cleaned)) {
            return true;
        }

        if (preg_match('/^0[0-9]{8,9}$/', $cleaned)) {
            return true;
        }

        if (preg_match('/^972[0-9]{9}$/', $cleaned)) {
            return true;
        }

        return false;
    }

    public static function normalize(string $phone): string
    {
        $cleaned = preg_replace('/[\s\-\(\)]/', '', trim($phone));

        if (str_starts_with($cleaned, '0')) {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '+972')) {
            return '0'.substr($cleaned, 4);
        }

        if (str_starts_with($cleaned, '972')) {
            return '0'.substr($cleaned, 3);
        }

        $digits = preg_replace('/\D/', '', $cleaned);
        if (strlen($digits) === 9) {
            return '0'.$digits;
        }

        return $cleaned;
    }
}
