<?php
namespace App\Payments;

use App\Models\TopupRequest;

interface PaymentMethod
{
    public function key(): string;
    public function instruction(TopupRequest $req): string;
    public function keyboard(TopupRequest $req): ?array;
}
