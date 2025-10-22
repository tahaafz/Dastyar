<?php

namespace App\Telegram\Nav;

enum NavTarget: string
{
    case Welcome  = 'welcome';
    case Provider = 'buy.provider';
    case Plan     = 'buy.plan';
    case Location = 'buy.location';
    case OS       = 'buy.os';
}

