<?php

namespace App\Pipelines\Telegram\Pipes;

use App\Gurds\Telegram\ChannelGate;
use App\Pipelines\Telegram\WebhookPayload;
use App\Telegram\Commands\StartCommand;
use Closure;

class EnforceChannelGate
{
    public function __construct(private ChannelGate $gate, private StartCommand $start)
    {
    }

    public function handle(WebhookPayload $payload, Closure $next)
    {
        $user = $payload->user;

        if (!$user || $user->is_admin || !$this->gate->isChannelLockOn()) {
            return $next($payload);
        }

        if ($payload->dto->cbData === 'confirm:channel') {
            if ($this->gate->confirmOrAlert($payload->dto->raw, $payload->dto->chatId, $payload->dto->userId)) {
                $this->start->resetToWelcome($user);
            }

            return response('ok');
        }

        if (!$this->gate->isMember($payload->dto->userId)) {
            $this->gate->sendJoinPrompt($payload->dto->chatId);

            return response('ok');
        }

        return $next($payload);
    }
}
