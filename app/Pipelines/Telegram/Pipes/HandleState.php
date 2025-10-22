<?php

namespace App\Pipelines\Telegram\Pipes;

use App\Pipelines\Telegram\WebhookPayload;
use App\Telegram\Core\Context;
use App\Telegram\Core\Registry;
use Closure;

class HandleState
{
    public function handle(WebhookPayload $payload, Closure $next)
    {
        if ($payload->user) {
            (new Context($payload->user, Registry::map()))->getState()->handle($payload->dto->raw);
        }

        return $next($payload);
    }
}
