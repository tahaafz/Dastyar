<?php

namespace App\Traits\Telegram;

trait PersistsData
{
    protected function putData(string $key, mixed $value): void
    {
        $p = $this->process();
        $d = $p->tg_data ?? [];
        $d[$key] = $value;
        $p->tg_data = $d;
        $p->save();
    }

    protected function getData(string $key, mixed $default = null): mixed
    {
        $p = $this->process();
        return ($p->tg_data ?? [])[$key] ?? $default;
    }
}
