<?php

namespace App\Pipelines\Telegram\Pipes;

use App\Gurds\Telegram\UpdateDeduplicator;
use App\Pipelines\Telegram\WebhookPayload;
use Closure;
use Illuminate\Support\Facades\Cache;

class PreventDuplicate
{
    public function __construct(private UpdateDeduplicator $deduplicator)
    {
    }

    public function handle(WebhookPayload $payload, Closure $next)
    {
        if (!$this->deduplicator->shouldProcess($payload->dto->updateId)) {
            return response('ok');
        }

        if (!$this->acquireContentLock($payload)) {
            return response('ok');
        }

        return $next($payload);
    }

    private function acquireContentLock(WebhookPayload $payload): bool
    {
        $chatId = $payload->dto->chatId;
        if (!$chatId) {
            return true;
        }

        $signature = $payload->dto->cbData
            ? 'cb:'.$payload->dto->cbData
            : ($payload->dto->text ? 'txt:'.$payload->dto->text : null);

        if (!$signature) {
            return true;
        }

        $key = sprintf('tg:dup:%s:%s', $chatId, md5($signature));

        return Cache::add($key, 1, now()->addSeconds(2));
    }
}
