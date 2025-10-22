<?php

namespace App\Pipelines\Telegram\Pipes;

use App\Gurds\Telegram\SpamGuard;
use App\Pipelines\Telegram\WebhookPayload;
use Closure;

class CheckSpam
{
    public function __construct(private SpamGuard $guard)
    {
    }

    public function handle(WebhookPayload $payload, Closure $next)
    {
        if (!$payload->user) {
            return $next($payload);
        }

        if (!$this->guard->checkOrBlock($payload->user)) {
            return response('ok');
        }

        return $next($payload);
    }
}
