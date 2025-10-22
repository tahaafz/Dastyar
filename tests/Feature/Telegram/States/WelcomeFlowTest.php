<?php

use App\Enums\Telegram\StateKey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('Welcome: shows main menu options on enter', function () {
    $kit = tg()->start(StateKey::Welcome);

    $lastMessage = $kit->fake->lastText();
    expect($lastMessage)->toContain(__('telegram.welcome'));

    $keyboard = $kit->fake->lastReplyKeyboard;
    expect($keyboard)->not->toBeNull()
        ->and($keyboard[0])->toContain(__('telegram.buttons.buy'))
        ->and($keyboard[0])->toContain(__('telegram.buttons.support'));
});
