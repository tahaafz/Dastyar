<?php

use App\Services\Firecrawl\Support\PayloadBuilder;

it('builds payload with defaults and overrides', function () {
    $builder = new PayloadBuilder([
        'only_main_content' => false,
        'timeout' => 15000,
        'proxy' => 'auto',
        'store_in_cache' => true,
        'include_tags' => [],
        'exclude_tags' => ['footer'],
    ]);

    $payload = $builder->build('https://example.com', [['type' => 'json']], ['storeInCache' => false]);

    expect($payload['url'])->toBe('https://example.com');
    expect($payload['timeout'])->toBe(15000);
    expect($payload['formats'])->toHaveCount(1);
    expect($payload['storeInCache'])->toBeFalse();
});
