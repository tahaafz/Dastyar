<?php

namespace App\Http\Controllers\Telegram;

use App\DTOs\Telegram\TelegramUpdateDTO;
use App\Pipelines\Telegram\Pipes\{
    AcquireChatLock,
    BootTelegramUser,
    CheckSpam,
    EnforceChannelGate,
    EnforcePrivateChat,
    EnsureIdentifiers,
    EnsureUserNotBlocked,
    HandleAdminInbox,
    HandleStartCommand,
    HandleState,
    PreventDuplicate
};
use App\Pipelines\Telegram\WebhookPayload;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function __invoke(Request $request, Pipeline $pipeline)
    {
        $payload = new WebhookPayload(TelegramUpdateDTO::from($request->all()));

        return $pipeline
            ->send($payload)
            ->through([
                PreventDuplicate::class,
                AcquireChatLock::class,
                EnsureIdentifiers::class,
                EnforcePrivateChat::class,
                BootTelegramUser::class,
                EnsureUserNotBlocked::class,
                CheckSpam::class,
                HandleAdminInbox::class,
                EnforceChannelGate::class,
                HandleStartCommand::class,
                HandleState::class,
            ])
            ->then(fn () => response('ok'));
    }
}
