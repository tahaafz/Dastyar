<?php

namespace App\Pipelines\Telegram\Pipes;

use App\Pipelines\Telegram\WebhookPayload;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class AcquireChatLock
{
    public function handle(WebhookPayload $payload, Closure $next)
    {
        $chatId = $payload->dto->chatId;
        if (!$chatId) {
            return $next($payload);
        }

        $lock = Cache::lock('tg:chat:lock:'.$chatId, 5);

        try {
            return $lock->block(3, fn () => $next($payload));
        } catch (LockTimeoutException) {
            return response('ok');
        } finally {
            if ($lock->owner()) {
                $lock->release();
            }
        }
    }
}
