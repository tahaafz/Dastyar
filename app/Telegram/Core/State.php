<?php

namespace App\Telegram\Core;

use Illuminate\Database\Eloquent\Model;

abstract class State
{
    protected Context $parent;

    public function setParent(Context $ctx): void { $this->parent = $ctx; }

    public function handle(array $update): void
    {
        if (method_exists($this, 'extract')) {
            [$text, $cbData] = $this->extract($update);
        } else {
            $text   = $update['message']['text'] ?? null;
            $cbData = $update['callback_query']['data'] ?? null;
        }

        if ($text !== null && method_exists($this, 'interceptShortcuts')) {
            if ($this->interceptShortcuts($text)) return;
        }

        if ($cbData !== null && method_exists($this, 'onCallback')) { $this->onCallback($cbData, $update); return; }
        if ($text   !== null && method_exists($this, 'onText'))     { $this->onText($text, $update);       return; }
    }

    /** @return \App\Models\User */
    protected function process(): Model { return $this->parent->record(); }

    abstract public function onEnter(): void;
}
