<?php

namespace App\Telegram\Commands;

use App\Enums\Telegram\StateKey;
use App\Models\User;
use App\Services\Cart\UserCart;
use App\Services\Telegram\DeepLinkService;
use App\Support\Telegram\Text;
use App\Telegram\Core\Context;
use App\Telegram\Core\Registry;

class StartCommand
{
    public function __construct(private readonly DeepLinkService $deepLinks)
    {
    }

    public function maybe(User $user, ?string $text): bool
    {
        if ($text === null) {
            return false;
        }

        $trimmed = trim($text);

        if (preg_match('/^\/start(?:\s+(.+))?$/i', $trimmed, $matches)) {
            $payload = isset($matches[1]) ? trim($matches[1]) : null;

            if ($payload !== null && $payload !== '') {
                return $this->handleStartPayload($user, $payload);
            }

            $this->resetToWelcome($user);
            return true;
        }

        $norm = Text::normalize($text);
        if (!$user->tg_current_state || $norm === 'start') {
            $this->resetToWelcome($user);
            return true;
        }
        return false;
    }

    public function resetToWelcome(User $user): void
    {
        $user->tg_current_state   = StateKey::Welcome->value;
        $user->tg_data            = null;
        $user->tg_last_message_id = null;
        $user->save();

        (new Context($user, Registry::map()))->getState()->onEnter();
    }

    private function handleStartPayload(User $user, string $payload): bool
    {
        if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $payload)) {
            $this->resetToWelcome($user);
            return true;
        }

        $data = $this->deepLinks->resolve($payload);

        if (!$data) {
            $this->resetToWelcome($user);
            return true;
        }

        $type = $data['type'] ?? null;

        if ($type === 'collect_link') {
            $link = (string) ($data['url'] ?? '');
            if ($link === '') {
                $this->resetToWelcome($user);
                return true;
            }

            $this->startCollectLinkFlow($user, $link);
            return true;
        }

        $this->resetToWelcome($user);
        return true;
    }

    private function startCollectLinkFlow(User $user, string $link): void
    {
        $data = (array) ($user->tg_data ?? []);
        unset(
            $data['choices'],
            $data['choices_ids'],
            $data['choices_codes'],
            $data['pending_link_id']
        );

        $user->forceFill([
            'tg_data'            => $data,
            'tg_current_state'   => StateKey::BuyCollectLink->value,
            'tg_last_message_id' => null,
        ])->save();

        UserCart::reset($user);

        $context = new Context($user, Registry::map());
        $state = $context->getState();

        $state->onText($link, ['message' => ['text' => $link]]);
    }
}
