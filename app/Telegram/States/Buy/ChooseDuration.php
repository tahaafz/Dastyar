<?php

namespace App\Telegram\States\Buy;

use App\Enums\Telegram\StateKey;
use App\Telegram\States\Support\CategoryDrivenState;

class ChooseDuration extends CategoryDrivenState
{
    protected StateKey $stateKey = StateKey::BuyChooseDuration;
    protected string   $textKey  = 'telegram.buy.choose_duration';
}
