<?php

namespace App\Notifications\Telegram;

use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Str;
use Telegram\Bot\Laravel\Facades\Telegram;

class AdminNotifier
{
    public function notifyAll(string $text): void
    {
        User::query()
            ->where('is_admin', true)
            ->whereNotNull('telegram_chat_id')
            ->chunkById(200, function ($admins) use ($text) {
                foreach ($admins as $a) {
                    try {
                        Telegram::sendMessage([
                            'chat_id' => $a->telegram_chat_id,
                            'parse_mode' => 'HTML',
                            'text' => $text,
                        ]);
                    } catch (\Throwable $e) {}
                }
            });
    }

    public function serverCreationFailed(Server $server, string $errorBody, array $payload = []): void
    {
        $max = 1500;
        $body = htmlspecialchars(Str::limit($errorBody, $max));

        $txt =
            "⚠️ <b>Server Create FAILED</b>\n".
            "UserID: <code>{$server->user_id}</code>\n".
            "Provider: <code>{$server->provider}</code>\n".
            "Plan: <code>{$server->plan}</code>\n".
            "Region: <code>{$server->region_id}</code>\n".
            "OS: <code>{$server->os_image_id}</code>\n".
            "Name: <code>{$server->name}</code>\n".
            "Payload: <code>".htmlspecialchars(Str::limit(json_encode($payload), $max))."</code>\n".
            "Response: <code>{$body}</code>";

        $this->notifyAll($txt);
    }

    public function serverActionFailed(Server $server, string $action, string $errorBody): void
    {
        $txt =
            "⚠️ <b>Server Action FAILED</b>\n".
            "Action: <code>{$action}</code>\n".
            "ServerID: <code>{$server->id}</code> | Ext: <code>{$server->external_id}</code>\n".
            "UserID: <code>{$server->user_id}</code>\n".
            "Err: <code>".htmlspecialchars(Str::limit($errorBody, 1500))."</code>";
        $this->notifyAll($txt);
    }
}
