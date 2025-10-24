<?php

namespace App\Telegram\Nav;

enum NavTarget: string
{
    case Welcome  = 'welcome';
    case Link     = 'buy.link';
    case Duration = 'buy.duration';
    case Review   = 'buy.review';
}
