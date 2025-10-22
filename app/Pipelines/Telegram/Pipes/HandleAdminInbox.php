<?php

namespace App\Pipelines\Telegram\Pipes;

use App\Pipelines\Telegram\WebhookPayload;
use App\Services\Telegram\Admin\AdminInboxRouter;
use Closure;

class HandleAdminInbox
{
    public function __construct(private AdminInboxRouter $router)
    {
    }

    public function handle(WebhookPayload $payload, Closure $next)
    {
        if ($payload->user && $this->router->maybeHandle($payload->user, $payload->dto)) {
            return response('ok');
        }

        return $next($payload);
    }
}
