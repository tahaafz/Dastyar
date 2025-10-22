<?php

namespace App\Telegram\States;

use App\Enums\Telegram\StateKey;
use App\Enums\SupportTicketType;
use App\Models\SupportTicket;
use App\Services\Telegram\Admin\AdminMessenger;
use App\Telegram\Core\AbstractState;
use App\Telegram\UI\KeyboardFactory;

class Support extends AbstractState
{
    public function onEnter(): void
    {
        $this->expireInlineScreen();
        $this->sendWithReplyKeyboard('telegram.support.enter', KeyboardFactory::replyBackOnly());
    }

    public function onText(string $text, array $u): void
    {
        if ($this->interceptShortcuts($text)) return;

        $user = $this->process();
        $chatId = $user->telegram_chat_id;
        $messageId = data_get($u, 'message.message_id');

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'message' => $text,
            'type'    => SupportTicketType::Question,
        ]);

        if ($chatId && $messageId) {
            $this->tgReact($chatId, (int) $messageId, [['type' => 'emoji', 'emoji' => '🙏']], true);
        }

        app(AdminMessenger::class)->broadcastSupportFromUser($ticket);
        $this->sendWithReplyKeyboard('telegram.support.sent', KeyboardFactory::replyBackOnly());

        $this->resetToWelcomeMenu($resetAnchor = false);
    }
}
