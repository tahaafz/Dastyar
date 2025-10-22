<?php

namespace App\Telegram\UI;

use App\Telegram\Callback\Action;
use App\Telegram\Callback\CallbackData;

final class InlineMenu
{
    public array $rows = [];
    public ?string $backTo   = null;
    public ?string $backKey  = null;

    public static function make(Row ...$rows): self
    {
        $m = new self; $m->rows = $rows; return $m;
    }

    public function backTo(string $stateKey, ?string $backLabelKey = 'telegram.buttons.back'): self
    {
        $this->backTo  = $stateKey;
        $this->backKey = $backLabelKey;
        return $this;
    }

    public function toTelegram(callable $pack): array
    {
        $inline = [];
        foreach ($this->rows as $row) {
            $tgRow = [];
            foreach ($row->buttons as $btn) {
                $raw = CallbackData::build($btn->action, $btn->params);
                $tgRow[] = [
                    'text' => $btn->labelKey ? __($btn->labelKey) : (string)$btn->label,
                    'callback_data' => $pack($raw),
                ];
            }
            $inline[] = $tgRow;
        }

        if ($this->backTo) {
            $raw = CallbackData::build(Action::NavBack, ['to' => $this->backTo]);
            $inline[] = [[
                'text' => __($this->backKey ?? 'telegram.buttons.back'),
                'callback_data' => $pack($raw),
            ]];
        }

        return ['inline_keyboard' => $inline];
    }
}
