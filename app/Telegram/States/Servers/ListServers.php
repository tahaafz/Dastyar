<?php

namespace App\Telegram\States\Servers;

use App\Telegram\Core\AbstractState;
use App\Telegram\UI\KeyboardFactory;

class ListServers extends AbstractState
{
    public function onEnter(): void
    {
        $this->send(__('telegram.servers.list'), KeyboardFactory::replyBackOnly());

        $user    = $this->process();
        $servers = \App\Models\Server::where('user_id',$user->id)->latest()->take(10)->get();

        if ($servers->isEmpty()) {
            $this->sendT('telegram.servers.empty');
        } else {
            $lines = $servers->map(fn($s)=> __('telegram.servers.item', [
                'id'=>$s->id,'provider'=>$s->provider,'region'=>$s->region,'plan'=>$s->plan
            ]))->implode("\n");
            $this->send($lines);
        }
    }
}
