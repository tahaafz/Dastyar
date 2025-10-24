<?php

namespace App\Services\Firecrawl;

use App\Services\Firecrawl\Contracts\FormatProvider;
use App\Services\Firecrawl\Exceptions\FirecrawlException;
use App\Services\Firecrawl\Support\FormatNormalizer;
use App\Services\Firecrawl\Support\PayloadBuilder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class FirecrawlService
{
    private readonly string $baseUrl;
    private readonly ?string $apiKey;
    private readonly PayloadBuilder $payloadBuilder;
    private readonly ?float $timeout;

    public function __construct()
    {
        $config = config('firecrawl', []);
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.firecrawl.dev/v2'), '/');
        $this->apiKey = $config['api_key'] ?? null;

        $defaults = $config['defaults'] ?? [
            'only_main_content' => false,
            'store_in_cache'    => true,
            'include_tags'      => [],
            'exclude_tags'      => ['nav', 'footer', 'header', '.ads', '#banner'],
        ];

        $this->payloadBuilder = new PayloadBuilder($defaults);
        $this->timeout = array_key_exists('http_timeout', $config)
            ? (float) $config['http_timeout']
            : 0.0;
    }

    /**
     * @param iterable<int, array<string, mixed>|FormatProvider> $formats
     * @param array<string, mixed> $overrides
     *
     * @throws FirecrawlException
     */
    public function scrape(string $url, iterable $formats, array $overrides = []): array
    {
        if ($url === '') {
            throw new FirecrawlException('A target URL is required for Firecrawl requests.');
        }

        if ($this->apiKey === null || $this->apiKey === '') {
            throw new FirecrawlException('Firecrawl API key is not configured.');
        }

        $payload = $this->payloadBuilder->build(
            $url,
            FormatNormalizer::normalize($formats),
            $overrides
        );

        try {
            $response = $this->request()->post('scrape', $payload);
        } catch (ConnectionException $exception) {
            throw new FirecrawlException(
                'Unable to reach Firecrawl API: ' . $exception->getMessage(),
                previous: $exception
            );
        }

        if ($response->failed()) {
            throw new FirecrawlException(sprintf(
                'Firecrawl API responded with %s: %s',
                $response->status(),
                $response->body()
            ));
        }

        return $response->json() ?? [];
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->withToken($this->apiKey, 'Bearer')
            ->withHeader('Content-Type', 'application/json');

        if ($this->timeout !== null) {
            $request = $request->timeout($this->timeout);
        }

        return $request;
    }
}
