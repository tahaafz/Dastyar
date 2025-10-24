<?php

namespace App\Traits\Telegram;

trait MainMenuShortcuts
{
    protected function interceptShortcuts(?string $text): bool
    {
        if ($text === null) return false;

        $buyLabel     = __('telegram.buttons.buy');
        $supportLabel = __('telegram.buttons.support');
        $topupLabel   = __('telegram.buttons.topup');
        $activeLinks  = __('telegram.buttons.active_links');
        $backMain     = __('telegram.buttons.back_main');

        switch ($text) {
            case $buyLabel:
                $this->expireInlineScreen();
                $this->newFlow();
                $this->goKey('buy.link');
                return true;

            case $supportLabel:
                $this->expireInlineScreen();
                $this->newFlow();
                $this->goKey('support');
                return true;

            case $activeLinks:
                $this->expireInlineScreen();
                $this->newFlow();
                $this->goKey('links.active');
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
