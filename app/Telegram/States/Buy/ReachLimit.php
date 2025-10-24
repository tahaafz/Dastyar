<?php

namespace App\Telegram\States\Buy;

use App\Enums\Telegram\StateKey;
use App\Telegram\Callback\Action;
use App\Telegram\Core\AbstractState;
use App\Telegram\UI\{Btn, InlineMenu, Row};

class ReachLimit extends AbstractState
{
    private const LIMIT_TOPUP_AMOUNT = 25000;

    public function onEnter(): void
    {
        $user = $this->process();
        $limit = max(0, (int) $user->links_limit);

        $menu = InlineMenu::make(
            Row::make(Btn::key('telegram.buttons.confirm', Action::LinkLimitConfirm))
        );
        $vars = [
            'limit'  => $limit,
            'amount' => number_format(self::LIMIT_TOPUP_AMOUNT),
        ];

        $markup = $menu->toTelegram(fn(string $raw) => $this->pack($raw));

        if ($user->tg_last_message_id) {
            $this->editT('telegram.buy.limit_reached', $vars, $markup);
            return;
        }

        $this->sendT('telegram.buy.limit_reached', $vars, $markup);
    }

    public function onCallback(string $callbackData, array $u): void
    {
        $parsed = $this->cbParse($callbackData, $u);
        if (!$parsed) {
            return;
        }

        $action = $parsed['action'];

        if ($action === Action::LinkLimitConfirm) {
            $this->handleConfirmation();
            return;
        }

        if ($action === Action::NavBack) {
            $target = (string) ($parsed['params']['to'] ?? '');
            if ($target === '') {
                $target = StateKey::Welcome->value;
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
        if ($payload === Action::LinkLimitConfirm->value) {
            $this->handleConfirmation();
            return;
        }

        if ($payload === Action::Back->value) {
            $this->goKey(StateKey::Welcome->value);
        }
    }

    private function handleConfirmation(): void
    {
        if ($this->chargeLimitFromBalance()) {
            $this->expireInlineScreen();
            $this->goKey(StateKey::BuyCollectLink->value);
            return;
        }

        $this->rememberLimitTopup();
        $this->goKey(StateKey::WalletWaitReceipt->value);
    }

    private function rememberLimitTopup(): void
    {
        $user = $this->process();
        $data = (array) ($user->tg_data ?? []);
        $data['topup_amount'] = self::LIMIT_TOPUP_AMOUNT;
        $user->forceFill(['tg_data' => $data])->save();
    }

    private function chargeLimitFromBalance(): bool
    {
        $user = $this->process();
        $balance = (int) $user->balance;

        if ($balance < self::LIMIT_TOPUP_AMOUNT) {
            return false;
        }

        $data = (array) ($user->tg_data ?? []);
        unset($data['topup_amount']);

        $newLimit = (int) $user->links_limit + 1;

        $user->forceFill([
            'balance'     => $balance - self::LIMIT_TOPUP_AMOUNT,
            'tg_data'     => $data,
            'links_limit' => $newLimit,
        ])->save();

        return true;
    }
}
