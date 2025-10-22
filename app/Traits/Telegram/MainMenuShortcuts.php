<?php

namespace App\Traits\Telegram;

trait MainMenuShortcuts
{
    protected function interceptShortcuts(?string $text): bool
    {
        if ($text === null) return false;

        $buyLabel     = __('telegram.buttons.buy');
        $supportLabel = __('telegram.buttons.support');
        $manageLabel  = __('telegram.buttons.manage');
        $topupLabel   = __('telegram.buttons.topup');
        $backMain     = __('telegram.buttons.back_main');

        switch ($text) {
            case $buyLabel:
                $this->expireInlineScreen();
                $this->newFlow();
                $this->goKey('buy.provider');
                return true;

            case $supportLabel:
                $this->expireInlineScreen();
                $this->newFlow();
                $this->goKey('support');
                return true;

            case $manageLabel:
                $this->expireInlineScreen();
                $this->newFlow();
                $this->goKey('servers.list');
                return true;

            case $topupLabel:
                $this->expireInlineScreen();
                $this->newFlow();
                $this->goKey('wallet.enter_amount');
                return true;

            case $backMain:
                $this->resetToWelcomeMenu();
                return true;
        }
        return false;
    }
}
