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
            StateKey::BuyCollectLink->value    => \App\Telegram\States\Buy\CollectLink::class,
            StateKey::BuyReachLimit->value     => \App\Telegram\States\Buy\ReachLimit::class,
            StateKey::BuyChooseDuration->value => \App\Telegram\States\Buy\ChooseDuration::class,
            StateKey::BuyReview->value         => \App\Telegram\States\Buy\Review::class,
            StateKey::BuyConfirm->value        => \App\Telegram\States\Buy\Confirm::class,
            StateKey::BuySubmit->value         => \App\Telegram\States\Buy\Submit::class,
            StateKey::ServersList->value       => \App\Telegram\States\Servers\ListServers::class,
            StateKey::WalletEnterAmount->value => \App\Telegram\States\Wallet\EnterAmount::class,
            StateKey::WalletWaitReceipt->value => \App\Telegram\States\Wallet\WaitReceipt::class,
        ];
    }
}
