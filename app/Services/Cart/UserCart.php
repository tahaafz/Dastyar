<?php

namespace App\Services\Cart;

use App\Models\User;

class UserCart
{
    public static function add(User $user, int $amount): void
    {
        $user->increment('cart_total', max(0, $amount));
    }

    public static function sub(User $user, int $amount): void
    {
        $amount = max(0, $amount);
        $current = (int) $user->cart_total;
        $newTotal = max(0, $current - $amount);
        if ($newTotal === $current) {
            return;
        }

        $user->forceFill(['cart_total' => $newTotal])->save();
    }

    public static function reset(User $user): void
    {
        $user->forceFill(['cart_total' => 0])->save();
    }

    public static function total(User $user): int
    {
        return (int) $user->cart_total;
    }
}
