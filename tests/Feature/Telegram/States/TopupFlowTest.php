<?php

use App\Enums\Telegram\StateKey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('Topup: user enters amount and waits for receipt submission', function () {
    $kit = tg()->start(StateKey::Welcome);

    $kit->press(__('telegram.buttons.topup'))
        ->expectState(StateKey::WalletEnterAmount)
        ->expectText('telegram.wallet.enter_amount');

    $kit->press('100000')
        ->expectState(StateKey::WalletWaitReceipt)
        ->expectText('telegram.wallet.send_receipt');

    expect($kit->fake->lastText())->toContain(__('telegram.wallet.send_receipt'));
});
