<?php

namespace App\Traits\Telegram;

trait SendsMessages
{
    use TgApi;

    protected function replyKeyboard(array $rows): array
    {
        return ['keyboard'=>$rows,'resize_keyboard'=>true,'one_time_keyboard'=>false];
    }

    protected function inlineKeyboard(array $rows): array
    {
        return [
            'inline_keyboard' => array_map(
                fn($r) => array_map(fn($b) => ['text'=>$b['text'],'callback_data'=>$b['data']], $r),
                $rows
            )
        ];
    }

    protected function send(string $text, ?array $replyMarkup = null, bool $trackMessage = true): void
    {
        $p = $this->process();
        if ($replyMarkup === null && method_exists($this, 'defaultReplyKeyboard')) {
            $replyMarkup = $this->defaultReplyKeyboard();
        }

        $resp = $this->tgSend($p->telegram_chat_id, $text, $replyMarkup ?: null);
        if ($trackMessage && $resp) {
            $messageId = $resp->messageId ?? $resp->message_id ?? null;

            if ($messageId) {
                $p->tg_last_message_id = (int) $messageId;
                $p->save();
            }
        }
    }

    protected function edit(string $text, ?array $replyMarkup = null): void
    {
        $p = $this->process();
        if (!$p->tg_last_message_id) { $this->send($text, $replyMarkup); return; }
        $this->tgEdit($p->telegram_chat_id, $p->tg_last_message_id, $text, $replyMarkup);
    }
}
