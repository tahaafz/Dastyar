<?php
namespace App\Payments;

use App\Payments\PaymentMethod;
use App\Models\TopupRequest;

class CardPayment implements PaymentMethod
{
    private string $cardNumber;
    private string $cardHolder;

    public function __construct()
    {
        $this->cardNumber = (string) config('payment.card_number');
        $this->cardHolder = (string) config('payment.card_holder');
    }

    public function key(): string { return 'card'; }

    public function instruction(TopupRequest $req): string
    {
        $card = $this->cardNumber;
        $name = $this->cardHolder;
        $amt  = number_format($req->amount);

        return __('telegram.payment.card.instruction_title')."\n"
            . __('telegram.payment.card.to_name', ['name' => $name])."\n"
            . __('telegram.payment.card.card_number', ['card' => $card])."\n\n"
            . __('telegram.payment.card.amount_line', ['amount' => $amt])."\n\n"
            . __('telegram.payment.card.after_payment');
    }

    public function keyboard(TopupRequest $req): ?array
    {
        return ['inline_keyboard' => [
            [[
                'text' => \App\Telegram\UI\Buttons::label('cancel'),
                'callback_data' => \App\Telegram\Callback\CallbackData::build(\App\Telegram\Callback\Action::TopupCancel, ['id' => $req->id])
            ]],
        ]];
    }
}
