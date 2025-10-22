<?php

namespace App\Pipelines\Telegram;

use App\DTOs\Telegram\TelegramUpdateDTO;
use App\Models\User;

class WebhookPayload
{
    public function __construct(
        public TelegramUpdateDTO $dto,
        public ?User $user = null,
    ) {
    }
}
