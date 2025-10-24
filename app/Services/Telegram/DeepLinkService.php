<?php

namespace App\Services\Telegram;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;

final class DeepLinkService
{
    private const CACHE_PREFIX = 'telegram:deeplink:';

    private int $ttl;

    public function __construct(private readonly CacheRepository $cache)
    {
        $this->ttl = (int) config('telegram.deeplink_ttl', 1800);
    }

    public function createCollectLinkToken(string $url): string
    {
        return $this->store([
            'type' => 'collect_link',
            'url'  => $url,
        ]);
    }

    public function resolve(string $token): ?array
    {
        return $this->cache->get($this->key($token));
    }

    private function store(array $payload): string
    {
        do {
            $token = Str::lower(Str::random(16));
        } while ($this->cache->has($this->key($token)));

        $this->cache->put($this->key($token), $payload, $this->ttl);

        return $token;
    }

    private function key(string $token): string
    {
        return self::CACHE_PREFIX . $token;
    }
}
