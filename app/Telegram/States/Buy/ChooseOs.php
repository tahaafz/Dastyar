<?php

namespace App\Telegram\States\Buy;

use App\Enums\Telegram\StateKey;
use App\Telegram\States\Support\CategoryDrivenState;

class ChooseOs extends CategoryDrivenState
{
    protected StateKey $stateKey = StateKey::BuyChooseOS;
    protected string   $textKey  = 'telegram.buy.choose_os';
}
