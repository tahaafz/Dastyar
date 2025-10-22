<?php

namespace App\Pipelines\Telegram\Pipes;

use App\Pipelines\Telegram\WebhookPayload;
use App\Traits\Telegram\TgApi;
use Closure;

class EnsureUserNotBlocked
{
    use TgApi;

    public function handle(WebhookPayload $payload, Closure $next)
    {
        $user = $payload->user;

        if ($user && $user->is_blocked) {
            $this->tgSend(
                $user->telegram_chat_id,
                __('telegram.blocked.by_admin')."\n".
                ($user->blocked_reason ? __('telegram.blocked.reason_prefix', ['reason' => $user->blocked_reason]) : '')
            );

            return response('ok');
        }

        return $next($payload);
    }
}
