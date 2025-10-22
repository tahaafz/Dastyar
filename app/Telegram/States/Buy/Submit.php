<?php

namespace App\Telegram\States\Buy;

use App\Telegram\Core\AbstractState;

class Submit extends AbstractState
{
    public function onEnter(): void
    {
        $this->editT('telegram.buy.submitting');

        $this->editT('telegram.buy.submitted');
    }
}
