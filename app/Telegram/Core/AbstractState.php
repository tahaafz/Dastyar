<?php

namespace App\Telegram\Core;

use App\Support\Telegram\Msg;
use App\Telegram\UI\ManagesScreens;
use App\Traits\Telegram\FlowToken;
use App\Traits\Telegram\MainMenuShortcuts;
use App\Traits\Telegram\PersistsData;
use App\Traits\Telegram\ReadsUpdate;
use App\Traits\Telegram\SendsMessages;

abstract class AbstractState extends State
{
    use ReadsUpdate;
    use SendsMessages;
    use PersistsData;
    use MainMenuShortcuts;
    use FlowToken;
    use ManagesScreens;


    protected function sendT(string $textOrKey, array $vars = [], ?array $replyMarkup=null, string $parseMode='HTML'): void
    { $this->send(Msg::resolve($textOrKey, $vars), $replyMarkup, $parseMode); }

    protected function editT(string $textOrKey, array $vars = [], ?array $replyMarkup=null, string $parseMode='HTML'): void
    { $this->edit(Msg::resolve($textOrKey, $vars), $replyMarkup, $parseMode); }

    protected function goEnum(\App\Enums\Telegram\StateKey $k): void { $this->parent->transitionTo($k->value); }
    protected function goKey(string $k): void { $this->parent->transitionTo($k); }

    protected function cbBuild(\App\Telegram\Callback\Action $a, array $p=[]): string
    { return $this->pack(\App\Telegram\Callback\CallbackData::build($a,$p)); }

    protected function cbParse(string $data, array $u): ?array
    {
        [$ok,$rest]=$this->validateCallback($data,$u); if(!$ok) return null;
        return \App\Telegram\Callback\CallbackData::parse($rest);
    }
}
