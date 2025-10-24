<?php

namespace App\Services\Firecrawl\Support;

final class DurationParser
{
    private const DIGIT_MAP = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    public static function hours(?string $value): int
    {
        if ($value === null || $value === '') {
            return 24;
        }

        $normalized = strtr($value, self::DIGIT_MAP);

        if (preg_match('/(\d{1,3})/', $normalized, $m)) {
            $hours = (int) $m[1];
            if ($hours === 0) {
                return 24;
            }

            return $hours;
        }

        return 24;
    }
}
