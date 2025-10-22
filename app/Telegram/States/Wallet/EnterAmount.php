<?php

// app/Telegram/States/Wallet/EnterAmount.php
namespace App\Telegram\States\Wallet;

use App\Telegram\Core\AbstractState;
use App\Telegram\UI\KeyboardFactory;

class EnterAmount extends AbstractState
{
    public function onEnter(): void
    {
        $this->expireInlineScreen();
        $this->sendWithReplyKeyboard('telegram.wallet.enter_amount', \App\Telegram\UI\KeyboardFactory::replyBackOnly());
    }

    public function onText(string $text, array $u): void
    {
        if ($this->interceptShortcuts($text)) return;

        $amount = (int)preg_replace('/[^\d]/u','',$text);
        if ($amount <= 0) {
            $this->sendWithReplyKeyboard('telegram.wallet.invalid_amount', \App\Telegram\UI\KeyboardFactory::replyBackOnly());
            return;
        }
        $this->putData('topup_amount',$amount);
        $this->goKey('wallet.wait_receipt');
    }
}
