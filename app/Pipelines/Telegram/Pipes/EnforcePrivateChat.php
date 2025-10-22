<?php

namespace App\Pipelines\Telegram\Pipes;

use App\Gurds\Telegram\PrivateChatGate;
use App\Pipelines\Telegram\WebhookPayload;
use Closure;

class EnforcePrivateChat
{
    public function __construct(private PrivateChatGate $gate)
    {
    }

    public function handle(WebhookPayload $payload, Closure $next)
    {
        if (!$this->gate->enforce($payload->dto)) {
            return response('ok');
        }

        return $next($payload);
    }
}
