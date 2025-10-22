<?php

use App\Enums\Telegram\StateKey;
use Tests\Telegram\Support\Kit;

if (!function_exists('tg')) {
    function tg(?\App\Models\User $user = null): Kit
    {
        return Kit::make($user);
    }
}

/**
 * Lite flow: each step is a string (button) or ['press' => '...', 'expect' => ...].
 * If 'expect' is omitted, we just ensure the bot produced some output.
 * $expectEnd can be a final state or a translation key.
 */
if (!function_exists('tg_flow_lite')) {
    function tg_flow_lite(string|StateKey $start, array $steps, string|StateKey|null $expectEnd = null): void
    {
        $kit = tg()->start($start);

        foreach ($steps as $i => $step) {
            if (is_string($step)) {
                $kit->press($step)->expectAnyMessage();
                continue;
            }
            if (!is_array($step) || !isset($step['press'])) {
                throw new InvalidArgumentException("Step #$i must be a string or ['press'=>..., 'expect'=>...?].");
            }
            $kit->press($step['press']);
            $exp = $step['expect'] ?? null;
            if ($exp instanceof StateKey) {
                $kit->expectState($exp);
            } elseif (is_string($exp) && str_contains($exp, '.')) {
                $kit->expectText($exp);
            } elseif (is_string($exp)) {
                $kit->expectState($exp);
            } else {
                $kit->expectAnyMessage();
            }
        }

        if (!is_null($expectEnd)) {
            if ($expectEnd instanceof StateKey) {
                $kit->expectState($expectEnd);
            } elseif (is_string($expectEnd) && str_contains($expectEnd, '.')) {
                $kit->expectText($expectEnd);
            } elseif (is_string($expectEnd)) {
                $kit->expectState($expectEnd);
            }
        }
    }
}

/**
 * Map-style shorthand: 'button' => expected (or null for "any output")
 */
if (!function_exists('tg_map')) {
    function tg_map(string|StateKey $start, array $pairs, string|StateKey|null $expectEnd = null): void
    {
        $steps = [];
        foreach ($pairs as $press => $expect) {
            $steps[] = is_null($expect) ? ['press' => $press] : ['press' => $press, 'expect' => $expect];
        }
        tg_flow_lite($start, $steps, $expectEnd);
    }
}
