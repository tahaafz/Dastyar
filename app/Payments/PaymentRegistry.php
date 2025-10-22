<?php
namespace App\Payments;


use App\Payments\CardPayment;

class PaymentRegistry
{
    public static function methods(): array
    {
        return [
            new CardPayment(),
        ];
    }

    public static function byKey(string $key): ?PaymentMethod
    {
        foreach (self::methods() as $m) if ($m->key()===$key) return $m;
        return null;
    }
}
