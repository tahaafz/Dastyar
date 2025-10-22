<?php

namespace App\Telegram\UI;

use App\Support\Telegram\Msg;
use Telegram\Bot\Laravel\Facades\Telegram;

trait ManagesScreens
{
    protected function normalizeMarkup(mixed $markup): mixed
    {
        return is_array($markup) ? json_encode($markup) : $markup;
    }

    protected function expireInlineScreen(): void
    {
        $user = $this->process();
        if ($user->tg_last_message_id) {
            Telegram::deleteMessage([
                'chat_id'    => $user->telegram_chat_id,
                'message_id' => $user->tg_last_message_id,
            ]);
            $user->tg_last_message_id = null;
            $user->save();
        }
    }

    protected function sendAnchor(string $textOrKey, array $inline): void
    {
        $user = $this->process();
        $sent = Telegram::sendMessage([
            'chat_id'      => $user->telegram_chat_id,
            'text'         => Msg::resolve($textOrKey),
            'reply_markup' => $this->normalizeMarkup($inline),
            'parse_mode'   => 'HTML',
        ]);
        $user->tg_last_message_id = data_get($sent, 'message_id');
        $user->save();
    }

    protected function ensureInlineScreen(string $textOrKey, array $inline, bool $resetAnchor = false): void
    {
        $user = $this->process();

        if ($resetAnchor && $user->tg_last_message_id) {
            $this->expireInlineScreen();
        }

        if ($user->tg_last_message_id) {
            Telegram::editMessageText([
                'chat_id'      => $user->telegram_chat_id,
                'message_id'   => $user->tg_last_message_id,
                'text'         => Msg::resolve($textOrKey),
                'reply_markup' => $this->normalizeMarkup($inline),
                'parse_mode'   => 'HTML',
            ]);
        } else {
            $this->sendAnchor($textOrKey, $inline);
        }
    }

    protected function resetToWelcomeMenu(bool $resetAnchor = true): void
    {
        $user = $this->process();

        if ($resetAnchor) {
            $this->expireInlineScreen();
        }

        Telegram::sendMessage([
            'chat_id'      => $user->telegram_chat_id,
            'text'         => __('telegram.welcome'),
            'reply_markup' => $this->normalizeMarkup(\App\Telegram\UI\KeyboardFactory::replyMain()),
            'parse_mode'   => 'HTML',
        ]);
    }

    protected function sendWithReplyKeyboard(string $textOrKey, array $replyKeyboard): void
    {
        $user = $this->process();
        Telegram::sendMessage([
            'chat_id'      => $user->telegram_chat_id,
            'text'         => Msg::resolve($textOrKey),
            'reply_markup' => $this->normalizeMarkup($replyKeyboard),
            'parse_mode'   => 'HTML',
        ]);
    }
}
