<?php

use App\Enums\Telegram\StateKey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('Buy: user completes initial order path', function () {
    $kit = tg()->start(StateKey::Welcome);

    $kit->press(__('telegram.buttons.buy'))
        ->expectState(StateKey::BuyChooseProvider)
        ->expectText('telegram.buy.choose_provider');

    $kit->press(__('telegram.providers.gcore'))
        ->expectState(StateKey::BuyChoosePlan)
        ->expectText('telegram.buy.choose_plan');

    $kit->press(__('telegram.plans.g2s_shared_1_1_25'))
        ->expectState(StateKey::BuyChooseLocation)
        ->expectText('telegram.buy.choose_location');

    $kit->press(__('telegram.regions.dubai'))
        ->expectState(StateKey::BuyChooseOS)
        ->expectText('telegram.buy.choose_os');

    $kit->press(__('telegram.os.ubuntu22'))
        ->expectState(StateKey::BuyConfirm);

    expect($kit->fake->lastText())->toContain('خلاصه سفارش');

    $kit->press(__('telegram.buttons.confirm'))
        ->expectState(StateKey::BuySubmit)
        ->expectText('telegram.buy.submitted');
});
