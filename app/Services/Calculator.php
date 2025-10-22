<?php

namespace App\Services;

use App\Models\User;

class Calculator
{
    public function summarize(User $user): array
    {
        $cart      = (int) $user->cart_total;
        $balance   = (int) $user->balance;
        $sufficient= $balance >= $cart;
        $deficit   = $sufficient ? 0 : ($cart - $balance);

        return compact('cart','balance','sufficient','deficit');
    }
}
