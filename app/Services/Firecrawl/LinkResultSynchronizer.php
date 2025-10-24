<?php

namespace App\Services\Firecrawl;

use App\Models\LinkResult;
use App\Models\UserLink;
use App\Support\Helpers\Normalize;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

class LinkResultSynchronizer
{
    /**
     * @return array{created: LinkResult[], updated: LinkResult[]}
     */
    public function sync(UserLink $link, array $response): array
    {
        $ads = $this->extractAds($response);
        if (empty($ads)) {
            return ['created' => [], 'updated' => []];
        }

        $existing = $link->linkResults()->get()->keyBy('link');
        $seen = [];
        $created = [];
        $updated = [];

        foreach ($ads as $ad) {
            $payload = is_array($ad) ? $ad : [];
            $url = (string) ($payload['url'] ?? $payload['link'] ?? '');
            $title = trim((string) ($payload['title'] ?? ''));

            if ($url === '' || $title === '' || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;

            $city = $payload['city'] ?? null;
            $price = $payload['price'] ?? null;

            $record = $existing->get($url);

            if ($record === null) {
                $this->createRecord(
                    link: $link,
                    title: $title,
                    city: $city,
                    price: $price,
                    url: $url,
                    payload: $payload,
                    existing: $existing,
                    created: $created,
                    updated: $updated
                );
                continue;
            }

            $currentTitle = Normalize::text($record->title);
            $incomingTitle = Normalize::text($title);

            if ($currentTitle !== $incomingTitle) {
                continue;
            }

            $currentPrice = Normalize::price($record->price);
            $incomingPrice = Normalize::price($price);

            if ($incomingPrice !== $currentPrice) {
                $record->price = $price;
                $record->payload = $payload;
                $record->save();
                $existing->put($url, $record);
                $updated[] = $record;
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    private function extractAds(array $response): array
    {
        $ads = data_get($response, 'data.json.ads');
        if (!is_array($ads)) {
            $ads = data_get($response, 'json.ads');
        }
        if (!is_array($ads)) {
            $ads = data_get($response, 'data.ads');
        }
        if (!is_array($ads)) {
            $ads = data_get($response, 'ads', []);
        }

        return is_array($ads) ? $ads : [];
    }

    private function createRecord(
        UserLink $link,
        string $title,
        ?string $city,
        ?string $price,
        string $url,
        array $payload,
        Collection $existing,
        array &$created,
        array &$updated
    ): void {
        try {
            $record = $link->linkResults()->create([
                'title'   => $title,
                'city'    => $city,
                'price'   => $price,
                'link'    => $url,
                'payload' => $payload,
            ]);

            $existing->put($url, $record);

            $created[] = $record;

            return;
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            /** @var LinkResult|null $record */
            $record = $link->linkResults()->where('link', $url)->first();
            if (!$record) {
                return;
            }

            $currentTitle = Normalize::text($record->title);
            $incomingTitle = Normalize::text($title);

            if ($currentTitle !== $incomingTitle) {
                return;
            }

            $currentPrice = Normalize::price($record->price);
            $incomingPrice = Normalize::price($price);

            if ($incomingPrice !== $currentPrice) {
                $record->price = $price;
                $record->payload = $payload;
                $record->save();
                $updated[] = $record;
            }

            $existing->put($url, $record);

            return;
        }
    }

}
