<?php

namespace App\Telegram\States\Buy;

use App\Enums\Telegram\StateKey;
use App\Telegram\States\Support\CategoryDrivenState;

class ChooseLocation extends CategoryDrivenState
{
    protected StateKey $stateKey = StateKey::BuyChooseLocation;
    protected string   $textKey  = 'telegram.buy.choose_location';
}
