<?php

namespace App\Enums\Telegram;

enum StateKey: string
{
    case Welcome           = 'welcome';
    case Support           = 'support';
    case BuyReview          = 'buy.review';

    case BuyChooseProvider = 'buy.provider';
    case BuyChoosePlan     = 'buy.plan';
    case BuyChooseLocation = 'buy.location';
    case BuyChooseOS       = 'buy.os';
    case BuyConfirm        = 'buy.confirm';
    case BuySubmit         = 'buy.submit';

    case ServersList       = 'servers.list';

    case WalletEnterAmount = 'wallet.enter_amount';
    case WalletWaitReceipt = 'wallet.wait_receipt';
    public function categorySlug(): ?string
    {
        return match ($this) {
            self::BuyChooseProvider => 'buy.provider',
            self::BuyChoosePlan     => 'buy.plan',
            self::BuyChooseLocation => 'buy.location',
            self::BuyChooseOS       => 'buy.os',
            self::BuyReview         => 'buy.review', // همین کتگوری یک دکمه تایید از DB دارد
            default                 => null,         // Confirm/Submit/...
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::BuyChooseProvider => self::BuyChoosePlan,
            self::BuyChoosePlan     => self::BuyChooseLocation,
            self::BuyChooseLocation => self::BuyChooseOS,
            self::BuyChooseOS       => self::BuyReview,
            self::BuyReview         => self::BuyConfirm,
            self::BuyConfirm        => null,
            default                 => null,
        };
    }

    public function back(): ?self
    {
        return match ($this) {
            self::BuyChoosePlan     => self::BuyChooseProvider,
            self::BuyChooseLocation => self::BuyChoosePlan,
            self::BuyChooseOS       => self::BuyChooseLocation,
            self::BuyReview         => self::BuyChooseOS,
            self::BuyConfirm        => self::BuyReview,
            default                 => null,
        };
    }
}
