<?php

namespace App\Telegram\States\Buy;

use App\Enums\Telegram\StateKey;
use App\Models\User;
use App\Models\UserLink;
use App\Services\UserLinkManager;
use App\Services\Cart\UserCart;
use App\Telegram\Core\AbstractState;

class CollectLink extends AbstractState
{
    public function onEnter(): void
    {
        $user = $this->process();

        if ($this->hasReachedLimit($user)) {
            $this->goKey(StateKey::BuyReachLimit->value);
            return;
        }

        $data = (array) ($user->tg_data ?? []);
        $hasLink = isset($data['choices']['buy.link']);

        if (!$hasLink) {
            UserCart::reset($user);
            unset($data['choices'], $data['choices_ids'], $data['choices_codes'], $data['pending_link_id']);
            $user->forceFill(['tg_data' => $data])->save();
        }

        $this->prompt();
    }

    public function onText(string $text, array $u): void
    {
        if ($this->interceptShortcuts($text)) {
            return;
        }

        $normalized = $this->validateDivarLink($text);
        if ($normalized === null) {
            $this->sendT('telegram.buy.invalid_link');
            return;
        }

        $user = $this->process();

        if ($this->hasReachedLimit($user)) {
            $this->goKey(StateKey::BuyReachLimit->value);
            return;
        }

        $chatId = $user->telegram_chat_id;
        $messageId = data_get($u, 'message.message_id');
        if ($chatId && $messageId) {
            $this->tgReact($chatId, (int) $messageId, [['type' => 'emoji', 'emoji' => '👀']], true);
        }

        $link = app(UserLinkManager::class)->ensureInactiveLink($user, $normalized);
        $data = (array) ($user->tg_data ?? []);

        $choices = (array) ($data['choices'] ?? []);
        $choices['buy.link'] = $normalized;

        $choiceIds = (array) ($data['choices_ids'] ?? []);
        unset($choiceIds['buy.link']);

        $choiceCodes = (array) ($data['choices_codes'] ?? []);
        unset($choiceCodes['buy.link']);

        $data['choices']       = $choices;
        $data['choices_ids']   = $choiceIds;
        $data['choices_codes'] = $choiceCodes;
        $data['pending_link_id'] = $link->id;

        $user->forceFill([
            'tg_data'            => $data,
            'tg_last_message_id' => null,
        ])->save();

        $this->goKey(StateKey::BuyChooseDuration->value);
    }

    private function prompt(): void
    {
        $user = $this->process();

        if ($user->tg_last_message_id) {
            $this->editT('telegram.buy.enter_link');
            return;
        }

        $this->sendT('telegram.buy.enter_link');
    }

    private function validateDivarLink(?string $value): ?string
    {
        $value ??= '';
        $candidate = trim($value);
        if ($candidate === '') {
            return null;
        }

        if (stripos($candidate, 'https:divar.ir/') === 0 && stripos($candidate, 'https://divar.ir/') !== 0) {
            $candidate = 'https://' . substr($candidate, strlen('https:'));
        }

        if (stripos($candidate, 'https://divar.ir/') !== 0) {
            return null;
        }

        return $candidate;
    }

    private function hasReachedLimit(User $user): bool
    {
        $limit = (int) $user->links_limit;
        if ($limit <= 0) {
            return false;
        }

        $active = UserLink::query()
            ->where('user_id', $user->id)
            ->active()
            ->count();

        return $active >= $limit;
    }
}
