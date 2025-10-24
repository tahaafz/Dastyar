<?php

namespace App\Enums\Telegram;

enum StateKey: string
{
    case Welcome          = 'welcome';
    case Support          = 'support';

    case BuyCollectLink   = 'buy.link';
    case BuyReachLimit     = 'buy.limit';
    case BuyChooseDuration = 'buy.duration';
    case BuyReview        = 'buy.review';
    case BuyConfirm       = 'buy.confirm';
    case BuySubmit        = 'buy.submit';

    case ServersList       = 'servers.list';
    case LinksActive       = 'links.active';

    case WalletEnterAmount = 'wallet.enter_amount';
    case WalletWaitReceipt = 'wallet.wait_receipt';

    public function categorySlug(): ?string
    {
        return match ($this) {
            self::BuyChooseDuration => 'buy.duration',
            self::BuyReview         => 'buy.review',
            default                 => null,
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::BuyCollectLink    => self::BuyChooseDuration,
            self::BuyChooseDuration => self::BuyReview,
            self::BuyReview         => self::BuyConfirm,
            self::BuyConfirm        => null,
            default                 => null,
        };
    }

    public function back(): ?self
    {
        return match ($this) {
            self::BuyChooseDuration => self::BuyCollectLink,
            self::BuyReview         => self::BuyChooseDuration,
            self::BuyConfirm        => self::BuyReview,
            default                 => null,
        };
    }
}
