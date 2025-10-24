<?php

namespace App\Services\Firecrawl\Support;

use App\Services\Firecrawl\Contracts\FormatProvider;
use App\Services\Firecrawl\Exceptions\FirecrawlException;

final class FormatNormalizer
{
    /**
     * @param iterable<int, array|FormatProvider> $formats
     * @return array<int, array<string, mixed>>
     *
     * @throws FirecrawlException
     */
    public static function normalize(iterable $formats): array
    {
        $normalized = [];
        foreach ($formats as $format) {
            if ($format instanceof FormatProvider) {
                $normalized[] = $format->toArray();
                continue;
            }

            if (is_array($format)) {
                $normalized[] = $format;
                continue;
            }

            throw new FirecrawlException('Unsupported format definition provided.');
        }

        return $normalized;
    }
}
