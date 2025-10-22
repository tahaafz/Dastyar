<?php

namespace App\Pipelines\Telegram\Pipes;

use App\Pipelines\Telegram\WebhookPayload;
use App\Services\Telegram\TelegramUserService;
use Closure;

class BootTelegramUser
{
    public function __construct(private TelegramUserService $users)
    {
    }

    public function handle(WebhookPayload $payload, Closure $next)
    {
        $payload->user = $this->users->bootOrUpdate($payload->dto);

        return $next($payload);
    }
}
