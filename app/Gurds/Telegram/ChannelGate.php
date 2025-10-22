<?php

namespace App\Gurds\Telegram;

use App\Traits\Telegram\TgApi;
use App\Telegram\UI\Buttons;
use Telegram\Bot\Laravel\Facades\Telegram;

class ChannelGate
{
    use TgApi;

    protected bool $lockOn;
    protected ?string $channelLink;

    public function __construct()
    {
        $this->lockOn = strtolower((string) config('telegram.channel.lock')) === 'on';
        $link = (string) config('telegram.channel.link');
        $this->channelLink = $link !== '' ? $link : null;
    }

    public function isChannelLockOn(): bool { return $this->lockOn; }

    public function chatIdForApi(): string { return $this->channelLink ? '@'.$this->channelLink : ''; }

    public function isMember(int|string $telegramUserId): bool
    {
        $chat = $this->chatIdForApi();
        if (!$chat) return false;

        try {
            $res = Telegram::getChatMember(['chat_id'=>$chat,'user_id'=>$telegramUserId]);
            $status = data_get($res, 'status');
            return in_array($status, ['member','administrator','creator','restricted'], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function sendJoinPrompt(int|string $chatId): void
    {
        $url  = $this->channelLink ? "https://t.me/{$this->channelLink}" : null;

        $kb = ['inline_keyboard' => array_filter([
            $url ? [ [ 'text' => Buttons::label('channel.join'), 'url' => $url ] ] : null,
            [ [ 'text' => Buttons::label('channel.check'), 'callback_data' => 'confirm:channel' ] ],
        ])];

        $this->tgSend($chatId, __('telegram.channel.prompt'), $kb);
    }

    public function confirmOrAlert(array $update, int|string $chatId, int|string $telegramUserId): bool
    {
        if ($this->isMember($telegramUserId)) {
            if ($mid = data_get($update, 'callback_query.message.message_id')) {
                $this->tgDelete($chatId, (int)$mid);
            }
            return true;
        }

        if ($cbId = data_get($update, 'callback_query.id')) {
            $this->tgToast($cbId, __('telegram.channel.not_member'), true, 3);
        }
        return false;
    }
}
