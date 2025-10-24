<?php

namespace App\Services\Telegram;

use App\Models\LinkResult;
use App\Models\User;
use Telegram\Bot\Laravel\Facades\Telegram;

class LinkResultMessenger
{
    /**
     * @param iterable<LinkResult> $results
     */
    public function send(User $user, iterable $results): void
    {
        $chatId = $user->telegram_chat_id;
        if (!$chatId) {
            return;
        }

        $messages = [];
        foreach ($results as $result) {
            $messages[] = $this->formatMessage($result);
        }

        if (empty($messages)) {
            return;
        }

        $payload = implode("\n\n", $messages);

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $payload,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
    }

    private function formatMessage(LinkResult $result): string
    {
        $title = htmlspecialchars($result->title, ENT_QUOTES, 'UTF-8');
        $link  = htmlspecialchars($result->link, ENT_QUOTES, 'UTF-8');

        $parts = ["<a href=\"{$link}\">{$title}</a>"];

        if ($result->price) {
            $parts[] = htmlspecialchars($result->price, ENT_QUOTES, 'UTF-8');
        }

        if ($result->city) {
            $parts[] = htmlspecialchars($result->city, ENT_QUOTES, 'UTF-8');
        }

        return implode(' - ', $parts);
    }
}
