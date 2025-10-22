<?php

namespace App\Support\Telegram;

use Illuminate\Support\Facades\Lang;

final class Msg
{
    public static function resolve(string $textOrKey, array $vars = [], ?string $locale=null): string
    {
        $translated = __($textOrKey, $vars, $locale);
        if ($translated === $textOrKey && !\Illuminate\Support\Facades\Lang::has($textOrKey, $locale)) {
            return '[MISSING: '.$textOrKey.']';
        }
        return $translated;
    }
}
