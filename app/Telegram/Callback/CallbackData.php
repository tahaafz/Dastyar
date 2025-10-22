<?php

namespace App\Telegram\Callback;

final class CallbackData
{
    /** ساخت: ACTION;key=val;key=val */
    public static function build(Action $a, array $params = []): string
    {
        $parts = [$a->value];
        foreach ($params as $k => $v) {
            $v = str_replace([';', '='], ['%3B', '%3D'], (string) $v);
            $parts[] = "{$k}={$v}";
        }
        return implode(';', $parts);
    }

    /** پارس: ['action'=>Action, 'params'=>['k'=>'v']] */
    public static function parse(string $data): ?array
    {
        $chunks = explode(';', $data);
        if (empty($chunks)) return null;

        $actionStr = array_shift($chunks);
        $action = self::tryAction($actionStr);
        if (!$action) return null;

        $params = [];
        foreach ($chunks as $c) {
            if (!str_contains($c, '=')) continue;
            [$k, $v] = explode('=', $c, 2);
            $params[$k] = str_replace(['%3B', '%3D'], [';', '='], $v);
        }
        return ['action' => $action, 'params' => $params];
    }

    private static function tryAction(string $v): ?Action
    {
        foreach (Action::cases() as $c) if ($c->value === $v) return $c;
        return null;
    }
}

