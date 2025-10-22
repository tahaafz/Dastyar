<?php

use App\Enums\Telegram\StateKey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('Manage: list servers and show welcome menu on back', function () {
    $kit = tg()->start(StateKey::Welcome);

    $kit->press(__('telegram.buttons.manage'))
        ->expectState(StateKey::ServersList);

    $texts = collect($kit->fake->messages)->pluck('text');
    expect($texts->contains(fn($text) => str_contains($text, __('telegram.servers.list'))))->toBeTrue()
        ->and($texts->contains(fn($text) => str_contains($text, __('telegram.servers.empty'))))->toBeTrue();

    $kit->press(__('telegram.buttons.back_main'))
        ->expectState(StateKey::ServersList)
        ->expectText('telegram.welcome');
});
