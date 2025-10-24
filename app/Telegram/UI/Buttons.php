<?php

namespace App\Telegram\UI;

use Illuminate\Support\Facades\Lang;

final class Buttons
{
    private static array $defaults = [
        'back'                   => '⬅️ Back',
        'buy'                    => 'دیوار',
        'support'                => 'Support',
        'management'             => 'Management',
        'topup'                  => 'Add balance',
        'approve'                => '✅ Approve',
        'reject'                 => '❌ Reject',
        'cancel'                 => 'Cancel',
        'reply'                  => '✍️ Reply',
        'channel.join'          => 'Join Channel',
        'channel.check'         => '✅ Joined, Check',
        'buy.confirm_and_send'  => '✅ Confirm & Submit',
        'buy.back'              => '⬅️ Back',
    ];

    public static function label(string $key, ?string $fallback = null): string
    {
        $locale = app()->getLocale();

        $k1 = "telegram.buttons.$key";
        if (Lang::has($k1, $locale)) return __($k1);

        $k2 = "telegram.$key";
        if (Lang::has($k2, $locale)) return __($k2);

        if ($fallback !== null) return $fallback;
        return self::$defaults[$key] ?? $key;
    }
}
