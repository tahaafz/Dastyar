<?php

namespace App\Telegram\UI;

use App\Telegram\Callback\Action;

final class Btn
{
    public function __construct(
        public ?string $label,
        public ?string $labelKey,
        public Action  $action,
        public array   $params = [],
    )
    {
    }

    public static function make(string $label, Action $action, array $params = []): self
    {
        return new self($label, null, $action, $params);
    }

    public static function key(string $labelKey, Action $action, array $params = []): self
    {
        return new self(null, $labelKey, $action, $params);
    }
}
