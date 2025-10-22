<?php

namespace App\Telegram\Commands;

use App\Enums\Telegram\StateKey;
use App\Models\User;
use App\Support\Telegram\Text;
use App\Telegram\Core\Context;
use App\Telegram\Core\Registry;

class StartCommand
{
    public function maybe(User $user, ?string $text): bool
    {
        $norm = Text::normalize($text);
        if (!$user->tg_current_state || $norm === '/start' || $norm === 'start') {
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
}
