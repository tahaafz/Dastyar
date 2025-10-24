<?php

namespace App\Services\Firecrawl\Formats;

use App\Services\Firecrawl\Contracts\FormatProvider;

final class DivarFormat implements FormatProvider
{
    public function toArray(): array
    {
        return [
            'type'   => 'json',
            'schema' => [
                'type'       => 'object',
                'properties' => [
                    'ads' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'price' => ['type' => ['string', 'null']],
                                'city' => ['type' => 'string', 'null'],
                                'url' => ['type' => 'string', 'format' => 'uri'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
