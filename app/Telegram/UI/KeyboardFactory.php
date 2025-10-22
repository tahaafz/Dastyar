<?php

namespace App\Telegram\UI;

final class KeyboardFactory
{
    public static function replyMain(): array
    {
        return [
            'keyboard' => [[
                __('telegram.buttons.buy'),
                __('telegram.buttons.support'),
                __('telegram.buttons.manage'),
            ],[
                __('telegram.buttons.topup'),
            ]],
            'resize_keyboard'   => true,
            'one_time_keyboard' => false,
        ];
    }
    public static function replyBackOnly(): array
    {
        return [
            'keyboard' => [[ __('telegram.buttons.back_main') ]],
            'resize_keyboard'   => true,
            'one_time_keyboard' => false,
        ];
    }
}
