<?php

use App\Enums\Telegram\StateKey;
use App\Models\UserLink;
use Database\Seeders\TelegramBuySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('Buy: user completes initial order path', function () {
    $this->seed(TelegramBuySeeder::class);

    $kit = tg()->start(StateKey::Welcome);
    $kit->user->forceFill(['balance' => 500000])->save();

    $kit->press(__('telegram.buttons.buy'))
        ->expectState(StateKey::BuyCollectLink)
        ->expectText('telegram.buy.enter_link');

    $kit->press('https://divar.ir/some-listing')
        ->expectState(StateKey::BuyChooseDuration)
        ->expectText('telegram.buy.choose_duration');

    assertDatabaseHas('user_links', [
        'user_id' => $kit->user->id,
        'url'     => 'https://divar.ir/some-listing',
        'status'  => UserLink::STATUS_INACTIVE,
        'type'    => 'divar',
    ]);

    $kit->press('۲۴ ساعت')
        ->expectState(StateKey::BuyReview)
        ->expectText('جزئیات سفارش');

    $kit->press('تایید سفارش')
        ->expectState(StateKey::BuySubmit)
        ->expectText('telegram.buy.submitted');

    expect($kit->fake->lastText())->toContain('درخواست شما ثبت شد');

    assertDatabaseHas('user_links', [
        'user_id' => $kit->user->id,
        'url'     => 'https://divar.ir/some-listing',
        'status'  => UserLink::STATUS_ACTIVE,
        'type'    => 'divar',
        'duration'=> '۲۴ ساعت',
    ]);

    $link = UserLink::query()->where('user_id', $kit->user->id)->where('url', 'https://divar.ir/some-listing')->first();
    expect($link?->active_at)->not()->toBeNull();
    expect($link?->expires_at)->not()->toBeNull();
    expect($link?->active_at->diffInDays($link->expires_at))->toBe(30.0);
});

it('Buy: reaches limit and uses balance to extend quota', function () {
    $this->seed(TelegramBuySeeder::class);

    $kit = tg()->start(StateKey::Welcome);
    $kit->user->forceFill([
        'balance'     => 100000,
        'links_limit' => 1,
    ])->save();

    UserLink::query()->create([
        'user_id' => $kit->user->id,
        'url'     => 'https://divar.ir/existing',
        'status'  => UserLink::STATUS_ACTIVE,
        'type'    => 'divar',
    ]);

    $kit->press(__('telegram.buttons.buy'))
        ->expectState(StateKey::BuyReachLimit)
        ->expectText('🚫 شما به سقف مجاز لینک‌های فعال');

    $kit->press(__('telegram.buttons.confirm'))
        ->expectState(StateKey::BuyCollectLink)
        ->expectText('telegram.buy.enter_link');

    $kit->user->refresh();

    expect($kit->user->balance)->toBe(100000 - 25000);
    expect($kit->user->links_limit)->toBe(2);
});
