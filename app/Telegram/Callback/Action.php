<?php

namespace App\Telegram\Callback;

enum Action: string
{
    case NavBack         = 'nav.back';
    case BuyPlan         = 'buy.plan';
    case BuyLocation     = 'buy.location';
    case BuyOS           = 'buy.os';
    case BuyConfirm      = 'buy.confirm';
    case ServersPanel    = 'srv.panel';
    case ServerAction    = 'srv.act';
    case TopupCancel     = 'topup.cancel';
    case TopupApprove    = 'topup.approve';
    case TopupReject     = 'topup.reject';
    case AdminReplyStart = 'admin.reply.start';

    case Back            = 'back';
    case CatalogPick     = 'cat.pick';        // انتخاب دکمه از tg_states (cat.pick:{id})
    case CheckoutTopup   = 'checkout.topup';  // رفتن به WaitReceipt
    case CheckoutSubmit  = 'checkout.submit'; //
}
