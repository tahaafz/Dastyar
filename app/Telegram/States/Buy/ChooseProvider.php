<?php

namespace App\Telegram\States\Buy;

use App\Enums\Telegram\StateKey;
use App\Telegram\States\Support\CategoryDrivenState;

class ChooseProvider extends CategoryDrivenState
{
    protected StateKey $stateKey        = StateKey::BuyChooseProvider;
    protected string   $textKey         = 'telegram.buy.choose_provider';
    protected bool     $resetCartOnEnter = true;
}
