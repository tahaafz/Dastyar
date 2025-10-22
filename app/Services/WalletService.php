<?php
namespace App\Services;

use App\Models\User;
use App\Models\TopupRequest;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function credit(User $user, int $amount, string $source, array $meta = []): void
    {
        DB::transaction(function () use ($user, $amount, $source, $meta) {
            $before = $user->balance;
            $after  = $before + $amount;

            $user->balance = $after;
            $user->save();

            WalletTransaction::create([
                'user_id' => $user->id,
                'type'    => 'credit',
                'amount'  => $amount,
                'currency'=> 'IRT',
                'source'  => $source,
                'balance_before' => $before,
                'balance_after'  => $after,
                'meta'    => $meta,
            ]);
        });
    }
}
