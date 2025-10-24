<?php

namespace App\Support\Helpers;

final class Normalize
{
    private const PERSIAN_DIGITS = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];


    public static function text(?string $value, string $encoding = 'UTF-8'): string
    {
        $normalized = trim((string) $value);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return mb_strtolower($normalized, $encoding);
    }


    public static function price(?string $value): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '';
        }

        $digits = preg_replace('/[^0-9۰-۹]/u', '', $normalized) ?? '';

        return strtr($digits, self::PERSIAN_DIGITS);
    }

    public static function number(?string $value): string
    {
        return self::price($value);
    }
}
