<?php

namespace App\Jobs\Firecrawl;

use App\Models\UserLink;
use App\Services\Firecrawl\FirecrawlService;
use App\Services\Firecrawl\Formats\DivarFormat;
use App\Services\Firecrawl\LinkResultSynchronizer;
use App\Services\Firecrawl\Support\DurationParser;
use App\Services\Telegram\LinkResultMessenger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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

    public int $tries = 3;
    public int $timeout = 300;

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
        LinkResultSynchronizer $synchronizer,
        LinkResultMessenger $messenger
    ): void {
        $now = Carbon::now();

        if ($this->userLinkId) {
            $link = UserLink::query()->with('user')->find($this->userLinkId);
            if (!$link || $link->status !== UserLink::STATUS_ACTIVE) {
                return;
            }

            $this->processLink($link, $firecrawl, $synchronizer, $messenger, $now);

            return;
        }

        UserLink::query()
            ->where('status', UserLink::STATUS_ACTIVE)
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->chunkById(50, function (Collection $links) use ($firecrawl, $synchronizer, $messenger, $now) {
                foreach ($links as $link) {
                    $this->processLink($link, $firecrawl, $synchronizer, $messenger, $now);
                }
            });
    }

    private function syncLink(
        FirecrawlService $firecrawl,
        LinkResultSynchronizer $synchronizer,
        UserLink $link
    ): array
    {
        $formats = $this->resolveFormats($link);
        if (empty($formats)) {
            return ['created' => [], 'updated' => []];
        }

        $response = $firecrawl->scrape($link->url, $formats);

        return $synchronizer->sync($link, $response);
    }

    private function resolveFormats(UserLink $link): array
    {
        return match ($link->type) {
            'divar' => [new DivarFormat()],
            default => [],
        };
    }

    private function processLink(
        UserLink $link,
        FirecrawlService $firecrawl,
        LinkResultSynchronizer $synchronizer,
        LinkResultMessenger $messenger,
        Carbon $now
    ): ?array
    {
        $intervalHours = max(1, DurationParser::hours($link->duration));
        $lastSynced = $link->last_synced_at;

        if ($lastSynced && $lastSynced->diffInMinutes($now) < $intervalHours * 60) {
            return null;
        }

        $link->loadMissing('user');
        $result = $this->syncLink($firecrawl, $synchronizer, $link);
        $changes = array_merge($result['created'], $result['updated']);

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
}
