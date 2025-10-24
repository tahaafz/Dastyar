<?php

namespace App\Telegram\States\Buy;

use App\Jobs\Firecrawl\SyncActiveLinksJob;
use App\Models\UserLink;
use App\Services\UserLinkManager;
use App\Telegram\Core\AbstractState;

class Submit extends AbstractState
{
    public function onEnter(): void
    {
        $this->editT('telegram.buy.submitting');

        $link = $this->activatePendingLink();

        if ($link) {
            SyncActiveLinksJob::schedule($link, true);
        }

        $this->editT('telegram.buy.submitted');
    }

    private function activatePendingLink(): ?UserLink
    {
        $user = $this->process();
        $data = (array) ($user->tg_data ?? []);
        $choices = (array) ($data['choices'] ?? []);
        $duration = isset($choices['buy.duration'])
            ? (string) $choices['buy.duration']
            : null;

        $link = app(UserLinkManager::class)->finalizePendingLink($user, $duration);

        unset($data['pending_link_id']);
        $user->forceFill(['tg_data' => $data])->save();

        return $link;
    }
}
