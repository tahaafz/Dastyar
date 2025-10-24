<?php

namespace App\Jobs\Firecrawl;

use App\Models\LinkResult;
use App\Models\UserLink;
use App\Services\Firecrawl\FirecrawlService;
use App\Services\Firecrawl\Formats\DivarFormat;
use App\Services\Firecrawl\Support\DurationParser;
use App\Services\Telegram\LinkResultMessenger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use Throwable;

class SyncActiveLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 90;

    public function __construct(private readonly ?int $userLinkId = null)
    {
    }

    public static function schedule(UserLink $link, bool $immediate = false): void
    {
        $dispatch = static::dispatch($link->id);

        if (!$immediate) {
            $intervalHours = max(1, DurationParser::hours($link->duration));
            $nextRun = Carbon::now()->addHours($intervalHours);
            if ($link->expires_at && $nextRun->greaterThan($link->expires_at)) {
                return;
            }

            $dispatch->delay($nextRun);
        }
    }

    public function handle(
        FirecrawlService $firecrawl,
        LinkResultMessenger $messenger
    ): void {
        $now = Carbon::now();

        if ($this->userLinkId) {
            $link = UserLink::query()->with('user')->find($this->userLinkId);
            if (!$link || $link->status !== UserLink::STATUS_ACTIVE) {
                return;
            }

            $this->processLink($link, $firecrawl, $messenger, $now);

            return;
        }

        UserLink::query()
            ->where('status', UserLink::STATUS_ACTIVE)
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->chunkById(50, function (Collection $links) use ($firecrawl, $messenger, $now) {
                foreach ($links as $link) {
                    $this->processLink($link, $firecrawl, $messenger, $now);
                }
            });
    }

    /**
     * @return LinkResult[]
     */
    private function syncLink(FirecrawlService $firecrawl, UserLink $link): array
    {
        $formats = $this->resolveFormats($link);
        if (empty($formats)) {
            return [];
        }

        $response = $firecrawl->scrape($link->url, $formats);

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

        if (!is_array($ads)) {
            return [];
        }

        $existing = $link->linkResults()->get()->keyBy('link');
        $seen = [];

        $changes = [];

        foreach ($ads as $ad) {
            $payload = is_array($ad) ? $ad : [];
            $url = (string) ($payload['url'] ?? $payload['link'] ?? '');
            $title = trim((string) ($payload['title'] ?? ''));

            if ($url === '' || $title === '') {
                continue;
            }

            if (isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;

            $city = $payload['city'] ?? null;
            $price = $payload['price'] ?? null;

            /** @var LinkResult|null $record */
            $record = $existing->get($url);

            if ($record === null) {
                try {
                    $record = $link->linkResults()->create([
                        'title'   => $title,
                        'city'    => $city,
                        'price'   => $price,
                        'link'    => $url,
                        'payload' => $payload,
                    ]);
                } catch (QueryException $e) {
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }

                    $record = $link->linkResults()
                        ->where('link', $url)
                        ->first();

                    if (!$record) {
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
                        $changes[] = $record;
                    }

                    $existing->put($url, $record);
                    continue;
                }

                $existing->put($url, $record);
                $changes[] = $record;
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
                $changes[] = $record;
            }
        }

        return $changes;
    }

    private function resolveFormats(UserLink $link): array
    {
        return match ($link->type) {
            'divar' => [new DivarFormat()],
            default => [],
        };
    }

    private function processLink(UserLink $link, FirecrawlService $firecrawl, LinkResultMessenger $messenger, Carbon $now): ?array
    {
        $intervalHours = max(1, DurationParser::hours($link->duration));
        $lastSynced = $link->last_synced_at;

        if ($lastSynced && $lastSynced->diffInMinutes($now) < $intervalHours * 60) {
            return null;
        }

        $link->loadMissing('user');
        $changes = $this->syncLink($firecrawl, $link);
        if (!empty($changes) && $link->user) {
            $messenger->send($link->user, $changes);
        }
        $link->forceFill(['last_synced_at' => $now])->save();
        $this->scheduleNext($link, $now);
        return $changes;
    }

    private function scheduleNext(UserLink $link, Carbon $now): void
    {
        $intervalHours = max(1, DurationParser::hours($link->duration));
        $nextRun = $now->copy()->addHours($intervalHours);

        if ($link->expires_at && $nextRun->greaterThan($link->expires_at)) {
            return;
        }

        static::dispatch($link->id)->delay($nextRun);
    }

    public static function purgeScheduled(int $userLinkId): void
    {
        $config = config('queue.connections.redis', []);

        $connection = $config['connection'] ?? 'default';
        $queueName  = $config['queue'] ?? 'default';

        $redis = Redis::connection($connection);

        $pendingKey = 'queues:'.$queueName;
        $delayedKey = $pendingKey.':delayed';

        $pending = $redis->lrange($pendingKey, 0, -1);
        foreach ($pending as $payload) {
            if (self::payloadMatches($payload, $userLinkId)) {
                $redis->lrem($pendingKey, 0, $payload);
            }
        }

        $delayed = $redis->zrange($delayedKey, 0, -1);
        foreach ($delayed as $payload) {
            if (self::payloadMatches($payload, $userLinkId)) {
                $redis->zrem($delayedKey, $payload);
            }
        }
    }

    private static function payloadMatches(string $payload, int $userLinkId): bool
    {
        $data = json_decode($payload, true);
        if (!is_array($data)) {
            return false;
        }

        $encoded = $data['data']['command'] ?? null;
        if (!is_string($encoded)) {
            return false;
        }

        $serialized = base64_decode($encoded, true);
        if ($serialized === false) {
            return false;
        }

        try {
            $instance = unserialize($serialized);
        } catch (Throwable) {
            return false;
        }

        return $instance instanceof self && $instance->userLinkId === $userLinkId;
    }
}
