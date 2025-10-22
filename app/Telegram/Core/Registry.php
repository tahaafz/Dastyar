<?php

namespace App\Telegram\Core;

use App\Enums\Telegram\StateKey;

final class Registry

{
    public static function map(): array
    {
        return [
            StateKey::Welcome->value           => \App\Telegram\States\Welcome::class,
            StateKey::Support->value           => \App\Telegram\States\Support::class,
            StateKey::BuyChooseProvider->value => \App\Telegram\States\Buy\ChooseProvider::class,
            StateKey::BuyChoosePlan->value     => \App\Telegram\States\Buy\ChoosePlan::class,
            StateKey::BuyChooseLocation->value => \App\Telegram\States\Buy\ChooseLocation::class,
            StateKey::BuyChooseOS->value       => \App\Telegram\States\Buy\ChooseOs::class,
            StateKey::BuyConfirm->value        => \App\Telegram\States\Buy\Confirm::class,
            StateKey::BuyReview->value     => \App\Telegram\States\Buy\Review::class,
            StateKey::BuySubmit->value         => \App\Telegram\States\Buy\Submit::class,
            StateKey::ServersList->value       => \App\Telegram\States\Servers\ListServers::class,
            StateKey::WalletEnterAmount->value => \App\Telegram\States\Wallet\EnterAmount::class,
            StateKey::WalletWaitReceipt->value => \App\Telegram\States\Wallet\WaitReceipt::class,
        ];
    }
}
