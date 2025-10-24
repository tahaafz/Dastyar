<?php

namespace App\Services\Firecrawl\Support;

final class PayloadBuilder
{
    public function __construct(
        private readonly array $defaults = []
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $formats
     * @param array<string, mixed> $overrides
     */
    public function build(string $url, array $formats, array $overrides = []): array
    {
        $payload = [
            'url'              => $url,
            'onlyMainContent'  => $this->defaults['only_main_content'] ?? false,
            'proxy'            => $this->defaults['proxy'] ?? 'auto',
            'storeInCache'     => $this->defaults['store_in_cache'] ?? true,
            'includeTags'      => $this->defaults['include_tags'] ?? [],
            'excludeTags'      => $this->defaults['exclude_tags'] ?? [],
            'formats'          => $formats,
        ];

        if (array_key_exists('timeout', $this->defaults)) {
            $payload['timeout'] = $this->defaults['timeout'];
        }

        foreach ($overrides as $key => $value) {
            $payload[$key] = $value;
        }

        return $payload;
    }
}
