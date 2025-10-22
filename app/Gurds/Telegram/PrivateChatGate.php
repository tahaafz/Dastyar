<?php

namespace App\Gurds\Telegram;

use App\DTOs\Telegram\TelegramUpdateDTO;
use App\Traits\Telegram\TgApi;

class PrivateChatGate
{
    use TgApi;

    public function enforce(TelegramUpdateDTO $dto): bool
    {
        if (!$dto->chatId || !$dto->userId) return false;

        if ((string)$dto->chatId !== (string)$dto->userId) {
            $this->tgSend($dto->chatId, __('telegram.errors.private_only'));
            return false;
        }
        return true;
    }
}
