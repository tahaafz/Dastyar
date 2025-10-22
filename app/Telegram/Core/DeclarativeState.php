<?php

namespace App\Telegram\Core;

use App\Telegram\Callback\Action;
use App\Telegram\UI\InlineMenu;
use App\Telegram\UI\ManagesScreens;

abstract class DeclarativeState extends AbstractState
{
    use ManagesScreens;

    abstract protected function screen(): array;
    abstract protected function routes(): array;

    public function onEnter(): void
    {
        $s = $this->screen(); $text=$s['text'] ?? '...'; $menu=$s['menu'] ?? null;

        if (!empty($s['hide_reply_keyboard'])) $this->hideReplyKeyboardOnce();

        if ($menu instanceof InlineMenu) {
            $this->ensureInlineScreen($text, $menu->toTelegram(fn($raw)=>$this->pack($raw)), resetAnchor: ($s['reset_anchor'] ?? false));
        } else {
            $this->sendT($text);
        }
    }

    public function onCallback(string $callbackData, array $u): void
    {
        $parsed = $this->cbParse($callbackData,$u); if(!$parsed){$this->onEnter();return;}
        $action=$parsed['action']; $params=$parsed['params'] ?? [];

        if ($action===Action::NavBack) {
            $to=(string)($params['to']??''); if($to==='welcome'){ $this->resetToWelcomeMenu(); return; }
            $this->goKey($to); return;
        }

        $map=$this->routes(); $h=$map[$action->value] ?? null;

        if (is_callable($h)) { $h($params,$this); return; }
        if (is_string($h))   { $this->goKey($h);   return; }
        if ($h instanceof \App\Enums\Telegram\StateKey){ $this->goEnum($h); return; }

        $this->onEnter();
    }

    public function onText(string $text, array $u): void
    {
        if ($this->interceptShortcuts($text)) return;
    }
}
