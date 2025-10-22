<?php

namespace App\Gurds\Telegram;

use Illuminate\Support\Facades\Cache;

class UpdateDeduplicator
{
    public function shouldProcess(?int $updateId, int $ttlSeconds = 180): bool
    {
        if (!$updateId) return true;
        return Cache::add('tg:update:'.$updateId, 1, now()->addSeconds($ttlSeconds));
    }
}
