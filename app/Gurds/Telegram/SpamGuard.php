<?php

namespace App\Gurds\Telegram;

use App\Models\User;
use App\Notifications\Telegram\AdminNotifier;
use App\Traits\Telegram\TgApi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SpamGuard
{
    use TgApi;

    public function checkOrBlock(User $user): bool
    {
        if ($user->is_admin) return true;

        $window    = 60;
        $threshold = 35;
        $key       = "tg:spamcnt:{$user->id}";

        $count = Cache::add($key, 0, $window) ? 0 : Cache::get($key, 0);
        $count++;
        Cache::put($key, $count, $window);

        $user->message_count = $user->message_count + 1;
        $user->last_message_at = Carbon::now();
        $user->save();

        if ($count > $threshold) {
            $user->is_blocked = true;
            $user->blocked_reason = __('telegram.spam.reason');
            $user->save();

            if ($user->telegram_chat_id) {
                $this->tgSend($user->telegram_chat_id, __('telegram.spam.user_blocked'));
            }

            app(AdminNotifier::class)->notifyAll(
                "🚫 <b>User blocked (SPAM)</b>\n".
                "UserID: <code>{$user->id}</code> • TG: <code>{$user->telegram_user_id}</code>\n".
                "Reason: ".__('telegram.spam.reason')."\nCount(window {$window}s): {$count}"
            );

            return false;
        }

        return true;
    }
}
