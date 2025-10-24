<?php

use App\Services\Firecrawl\Exceptions\FirecrawlException;
use App\Services\Firecrawl\Formats\DivarFormat;
use App\Services\Firecrawl\Support\FormatNormalizer;

it('normalizes array and provider formats', function () {
    $input = [
        ['type' => 'text'],
        new DivarFormat(),
    ];

    $result = FormatNormalizer::normalize($input);

    expect($result)->toHaveCount(2);
    expect($result[0])->toMatchArray(['type' => 'text']);
    expect($result[1]['schema']['properties'])->toHaveKey('ads');
});

it('throws when format type is unsupported', function () {
    FormatNormalizer::normalize([123]);
})->throws(FirecrawlException::class);
