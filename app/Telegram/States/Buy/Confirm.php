<?php

namespace App\Telegram\States\Buy;

use App\Enums\Telegram\StateKey;
use App\Services\Calculator;
use App\Telegram\Callback\Action;
use App\Telegram\Core\AbstractState;
use App\Telegram\UI\{Btn, Row, InlineMenu};

class Confirm extends AbstractState
{
    public function onEnter(): void
    {
        $user = $this->process();
        $sum  = (new Calculator())->summarize($user);

        if ($sum['sufficient']) {
            $user->balance   -= (int) $user->cart_total;
            $user->cart_total = 0;
            $user->save();

            $this->goKey(StateKey::BuySubmit->value);
            return;
        }

        $menu = InlineMenu::make(
            Row::make(Btn::key('telegram.buttons.increase_balance', Action::CheckoutTopup))
        );

        $this->editT(
            'telegram.buy.checkout.insufficient',
            [
                'cart'    => number_format($sum['cart']),
                'balance' => number_format($sum['balance']),
            ],
            $menu->toTelegram(fn(string $raw) => $this->pack($raw))
        );
    }

    public function onCallback(string $callbackData, array $u): void
    {
        $parsed = $this->cbParse($callbackData, $u);
        if (!$parsed) {
            return;
        }

        $action = $parsed['action'];
        if ($action === Action::CheckoutTopup) {
            $this->rememberDeficit();
            $this->goKey(StateKey::WalletWaitReceipt->value);
            return;
        }

        if ($action === Action::NavBack) {
            $target = (string) ($parsed['params']['to'] ?? '');
            if ($target === '') {
                $target = StateKey::BuyReview->value;
            }

            if ($target === 'welcome') {
                $this->resetToWelcomeMenu();
                return;
            }

            $this->goKey($target);
        }
    }

    public function onText(string $text, array $u): void
    {
        if ($this->interceptShortcuts($text)) {
            return;
        }

        $payload = trim($text);

        if ($payload === Action::CheckoutTopup->value) {
            $this->rememberDeficit();
            $this->goKey(StateKey::WalletWaitReceipt->value);
            return;
        }

        if ($payload === Action::Back->value) {
            $this->goKey(StateKey::BuyReview->value);
        }
    }

    private function rememberDeficit(): void
    {
        $user = $this->process();
        $sum  = (new Calculator())->summarize($user);
        $deficit = (int) $sum['deficit'];

        if ($deficit <= 0) {
            return;
        }

        $data = (array) ($user->tg_data ?? []);
        $data['topup_amount'] = $deficit;
        $user->forceFill(['tg_data' => $data])->save();
    }
}
