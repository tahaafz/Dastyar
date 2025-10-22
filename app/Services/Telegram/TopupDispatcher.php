<?php
namespace App\Services\Telegram;

use App\Models\TopupRequest;
use App\Models\User;
use App\Traits\Telegram\TgApi;
use App\Telegram\UI\Buttons;

class TopupDispatcher
{
    use TgApi;

    public function sendToAdmins(TopupRequest $req): void
    {
        $cap = implode("\n", [
            __('telegram.topup.request_title'),
            __('telegram.topup.line_user', ['user' => $req->user_id]),
            __('telegram.topup.line_amount', ['amount' => number_format($req->amount)]),
            __('telegram.topup.line_method', ['method' => $req->method]),
            __('telegram.topup.line_id', ['id' => $req->id]),
        ]);

        $kb = [
            'inline_keyboard' => [[
                [
                    'text' => Buttons::label('approve'),
                    'callback_data' => \App\Telegram\Callback\CallbackData::build(\App\Telegram\Callback\Action::TopupApprove, ['id' => $req->id]),
                ], [
                    'text' => Buttons::label('reject'),
                    'callback_data' => \App\Telegram\Callback\CallbackData::build(\App\Telegram\Callback\Action::TopupReject, ['id' => $req->id]),
                ],
            ]]
        ];

        $admins = User::query()->where('is_admin',true)->whereNotNull('telegram_chat_id')->get();
        foreach ($admins as $a) {
            if ($req->receipt_file_id) {
                $this->tgSendPhoto($a->telegram_chat_id, $req->receipt_file_id, $cap, $kb);
            } else {
                $this->tgSend($a->telegram_chat_id, $cap, $kb);
            }
        }
    }
}
