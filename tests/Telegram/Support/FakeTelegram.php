<?php

namespace Tests\Telegram\Support;

class FakeTelegram
{
    public array $messages = [];
    public array $edits = [];
    public array $answers = [];
    public array $deletes = [];

    public ?array $lastInlineKeyboard = null;
    public ?array $lastReplyKeyboard = null;

    private int $messageId = 0;

    public function reset(): void
    {
        $this->messages = [];
        $this->edits = [];
        $this->answers = [];
        $this->deletes = [];
        $this->lastInlineKeyboard = null;
        $this->lastReplyKeyboard = null;
        $this->messageId = 0;
    }

    public function sendMessage(array $params): object
    {
        $this->extractKeyboard($params['reply_markup'] ?? null);
        $this->messages[] = $params;
        $this->messageId++;
        return (object)['messageId' => $this->messageId];
    }

    public function editMessageText(array $params): object
    {
        $this->extractKeyboard($params['reply_markup'] ?? null);
        $this->edits[] = $params;
        return (object)['ok' => true];
    }

    public function answerCallbackQuery(array $params): object
    {
        $this->answers[] = $params;
        return (object)['ok' => true];
    }

    public function deleteMessage(array $params): object
    {
        $this->deletes[] = $params;
        return (object)['ok' => true];
    }

    public function lastText(): ?string
    {
        if (!empty($this->edits)) {
            return $this->edits[array_key_last($this->edits)]['text'] ?? null;
        }
        if (!empty($this->messages)) {
            return $this->messages[array_key_last($this->messages)]['text'] ?? null;
        }
        return null;
    }

    private function extractKeyboard($replyMarkup): void
    {
        if (!$replyMarkup) return;
        $decoded = is_string($replyMarkup) ? json_decode($replyMarkup, true) : $replyMarkup;
        if (!is_array($decoded)) return;
        if (isset($decoded['inline_keyboard'])) $this->lastInlineKeyboard = $decoded['inline_keyboard'];
        if (isset($decoded['keyboard'])) $this->lastReplyKeyboard = $decoded['keyboard'];
    }
}
