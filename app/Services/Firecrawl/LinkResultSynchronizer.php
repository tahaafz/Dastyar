<?php

namespace App\Services\Firecrawl;

use App\Models\LinkResult;
use App\Models\UserLink;
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

            /** @var LinkResult|null $record */
            $record = $existing->get($url);

            if ($record === null) {
                $record = $this->createRecord($link, $title, $city, $price, $url, $payload, $existing, $updated);
                if ($record) {
                    $created[] = $record;
                }
                continue;
            }

            $record->fill([
                'title'   => $title,
                'city'    => $city,
                'price'   => $price,
                'payload' => $payload,
            ]);

            if ($record->isDirty(['title', 'city', 'price', 'payload'])) {
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

    /**
     * @param \Illuminate\Support\Collection<string, LinkResult> $existing
     * @param array<int, LinkResult>                              $updated
     */
    private function createRecord(
        UserLink $link,
        string $title,
        ?string $city,
        ?string $price,
        string $url,
        array $payload,
        Collection $existing,
        array &$updated
    ): ?LinkResult {
        try {
            $record = $link->linkResults()->create([
                'title'   => $title,
                'city'    => $city,
                'price'   => $price,
                'link'    => $url,
                'payload' => $payload,
            ]);

            $existing->put($url, $record);

            return $record;
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            /** @var LinkResult|null $record */
            $record = $link->linkResults()->where('link', $url)->first();
            if (!$record) {
                return null;
            }

            $record->fill([
                'title'   => $title,
                'city'    => $city,
                'price'   => $price,
                'payload' => $payload,
            ]);

            if ($record->isDirty(['title', 'city', 'price', 'payload'])) {
                $record->save();
                $updated[] = $record;
            }

            $existing->put($url, $record);

            return $record;
        }
    }
}
