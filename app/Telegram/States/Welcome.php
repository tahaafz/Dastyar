<?php

namespace App\Telegram\States;

use App\Telegram\Core\AbstractState;
use App\Telegram\UI\KeyboardFactory;

class Welcome extends AbstractState
{
    public function onEnter(): void
    {
        $user = $this->process();
                $tg = (array) ($user->tg_data ?? []);

                if (!data_get($tg, 'ui.main_reply_ready', false)) {
                $this->sendT('telegram.welcome', [], KeyboardFactory::replyMain());

                data_set($tg, 'ui.main_reply_ready', true);
                $user->forceFill(['tg_data' => $tg])->save();
            }

           $this->sendT('telegram.choose');
    }
}
