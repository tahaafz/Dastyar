<?php

namespace App\Telegram\UI;

final class Row
{
    public array $buttons;

    public function __construct(Btn ...$buttons)
    {
        $this->buttons = $buttons;
    }

    public static function make(Btn ...$buttons): self
    {
        return new self(...$buttons);
    }
}
