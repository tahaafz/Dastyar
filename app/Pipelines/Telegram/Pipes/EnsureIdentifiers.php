<?php

namespace App\Pipelines\Telegram\Pipes;

use App\Pipelines\Telegram\WebhookPayload;
use Closure;

class EnsureIdentifiers
{
    public function handle(WebhookPayload $payload, Closure $next)
    {
        if (!$payload->dto->chatId || !$payload->dto->userId) {
            return response('ok');
        }

        return $next($payload);
    }
}
