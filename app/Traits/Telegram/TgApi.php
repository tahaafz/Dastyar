<?php

namespace App\Traits\Telegram;

use Telegram\Bot\Laravel\Facades\Telegram;
trait TgApi
{
    protected function tgSend(int|string $chatId, string $text, ?array $replyMarkup = null, string $parseMode = 'HTML'): ?object
    {
        if (is_array($text)) {
            $text = json_encode($text);
        }
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
        ];
        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        return Telegram::sendMessage($payload);
    }

    protected function tgEdit(int|string $chatId, int $messageId, string $text, ?array $replyMarkup = null, string $parseMode = 'HTML'): ?object
    {
        if (is_array($text)) {
            $text = json_encode($text);
        }
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
        ];
        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }
        return Telegram::editMessageText($payload);
    }

    protected function tgDelete(int|string $chatId, int $messageId): void
    {
        Telegram::deleteMessage(['chat_id' => $chatId, 'message_id' => $messageId]);
    }

    protected function tgToast(string $callbackQueryId, string $text, bool $alert = false, int $cacheTime = 0): void
    {
        Telegram::answerCallbackQuery([
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $alert,
            'cache_time' => $cacheTime,
        ]);
    }
    protected function tgSendPhoto(int|string $chatId, string $fileId, string $caption = '', ?array $replyMarkup = null, string $parseMode='HTML'): ?object
    {
        $payload = [
            'chat_id' => $chatId,
            'photo'   => $fileId,
            'caption' => $caption,
            'parse_mode' => $parseMode,
        ];
        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }
        return Telegram::sendPhoto($payload);
    }

    /**
     * Reacts to a message with the provided reaction payload.
     */
    protected function tgReact(int|string $chatId, int $messageId, array $reaction, bool $isBig = false): bool
    {
        if (empty($reaction)) {
            return false;
        }

        $reactionPayload = array_is_list($reaction) ? $reaction : [$reaction];

        $payload = [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'reaction'   => json_encode($reactionPayload),
        ];

        if ($isBig) {
            $payload['is_big'] = true;
        }

        $response = Telegram::post('setMessageReaction', $payload);
        $decoded = $response->getDecodedBody();

        return (bool) ($decoded['result'] ?? false);
    }
}
