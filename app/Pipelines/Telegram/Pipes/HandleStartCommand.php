<?php

namespace App\Pipelines\Telegram\Pipes;

use App\Pipelines\Telegram\WebhookPayload;
use App\Telegram\Commands\StartCommand;
use Closure;

class HandleStartCommand
{
    public function __construct(private StartCommand $start)
    {
    }

    public function handle(WebhookPayload $payload, Closure $next)
    {
        if ($payload->user && $this->start->maybe($payload->user, $payload->dto->text)) {
            return response('ok');
        }

        return $next($payload);
    }
}
