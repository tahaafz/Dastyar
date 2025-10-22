<?php

namespace App\Services\Telegram;

use App\DTOs\Telegram\TelegramUpdateDTO;
use App\Models\User;
use App\Support\Telegram\Text;
use Illuminate\Support\Str;

class TelegramUserService
{
    public function bootOrUpdate(TelegramUpdateDTO $dto): User
    {
        $user = User::firstOrCreate(
            ['telegram_user_id' => $dto->userId],
            [
                'telegram_chat_id' => $dto->chatId,
                'name'     => Text::sanitizeName($dto->firstName),
                'username' => $dto->username,
                'password' => bcrypt(Str::random(16)),
            ]
        );

        $dirty = false;
        if ($dto->chatId && $user->telegram_chat_id != $dto->chatId) { $user->telegram_chat_id = $dto->chatId; $dirty = true; }
        if ($dto->username && $user->username !== $dto->username)    { $user->username = $dto->username;       $dirty = true; }
        if ($dto->firstName) {
            $san = Text::sanitizeName($dto->firstName);
            if ($san && $user->name !== $san) { $user->name = $san; $dirty = true; }
        }
        if ($dirty) $user->save();

        return $user;
    }
}
