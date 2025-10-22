<?php

namespace App\Support\Telegram;

final class Text
{
    public static function normalize(?string $text): ?string
    {
        if ($text === null) return null;
        $t = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $t = trim($t);
        if ($t === '') return null;
        return mb_strtolower($t, 'UTF-8');
    }

    public static function sanitizeName(?string $name): ?string
    {
        if ($name === null) return null;
        $name = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $name);
        return trim($name) ?: null;
    }

    public static function digitsToEn(string $s): string {
        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٬','،',',',' '];
        $en = ['0','1','2','3','4','5','6','7','8','9','','','', ''];
        return str_replace($fa, $en, $s);
    }

    public static function parseAmountToman(?string $text): ?int {
        if ($text === null) return null;
        $t = self::digitsToEn($text);
        $t = preg_replace('/[^\d]/', '', $t);
        if ($t === '') return null;
        $amount = (int)$t;
        return $amount > 0 ? $amount : null;
    }
}
