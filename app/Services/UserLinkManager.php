<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLink;
use Carbon\CarbonImmutable;

class UserLinkManager
{
    public function ensureInactiveLink(User $user, string $url): UserLink
    {
        $inactive = UserLink::query()
            ->where('user_id', $user->id)
            ->where('status', UserLink::STATUS_INACTIVE)
            ->orderByDesc('id')
            ->first();

        if ($inactive) {
            $inactive->forceFill([
                'type'       => 'divar',
                'url'        => $url,
                'status'     => UserLink::STATUS_INACTIVE,
                'duration'   => null,
                'active_at'  => null,
                'expires_at' => null,
            ])->save();

            return $inactive;
        }

        return UserLink::query()->create([
            'user_id'    => $user->id,
            'type'       => 'divar',
            'url'        => $url,
            'status'     => UserLink::STATUS_INACTIVE,
            'duration'   => null,
            'active_at'  => null,
            'expires_at' => null,
        ]);
    }

    public function finalizePendingLink(User $user, ?string $duration, ?string $fallbackUrl = null): ?UserLink
    {
        $data = (array) ($user->tg_data ?? []);
        $linkId = (int) ($data['pending_link_id'] ?? 0);

        if ($linkId > 0) {
            $link = UserLink::query()
                ->where('id', $linkId)
                ->where('user_id', $user->id)
                ->first();
        } else {
            $link = null;
        }

        if (!$link && $fallbackUrl) {
            $link = UserLink::query()
                ->where('user_id', $user->id)
                ->where('url', $fallbackUrl)
                ->orderByDesc('id')
                ->first();

            if (!$link) {
                $link = $this->ensureInactiveLink($user, $fallbackUrl);
            }
        }

        if (!$link) {
            return null;
        }

        $now = CarbonImmutable::now();

        $link->forceFill([
            'status'     => UserLink::STATUS_ACTIVE,
            'duration'   => $duration,
            'active_at'  => $now,
            'expires_at' => $now->addDays(30),
        ])->save();

        return $link;
    }
}
