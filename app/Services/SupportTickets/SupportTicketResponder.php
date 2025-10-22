<?php

namespace App\Services\SupportTickets;

use App\Enums\SupportTicketType;
use App\Models\SupportTicket;
use App\Models\User;
use App\Traits\Telegram\TgApi;
use Illuminate\Validation\ValidationException;

class SupportTicketResponder
{
    use TgApi;

    public function respond(SupportTicket $ticket, User $admin, string $message): void
    {
        $message = trim($message);

        if ($message === '') {
            throw ValidationException::withMessages([
                'message' => __('Answer message cannot be empty.'),
            ]);
        }

        if ($ticket->type !== SupportTicketType::Question) {
            throw ValidationException::withMessages([
                'message' => __('Only question tickets can be answered.'),
            ]);
        }

        if (!$admin->is_admin) {
            throw ValidationException::withMessages([
                'message' => __('Only admins can reply to support tickets.'),
            ]);
        }

        $ticket->loadMissing('user');
        $user = $ticket->user;

        if (!$user) {
            throw ValidationException::withMessages([
                'message' => __('Ticket owner is not available.'),
            ]);
        }

        if (!$user->telegram_chat_id) {
            throw ValidationException::withMessages([
                'message' => __('Cannot send the reply because the user is not connected to Telegram.'),
            ]);
        }

        $ticket->markAsAnswered($message, $admin);

        $this->tgSend(
            $user->telegram_chat_id,
            __('telegram.admin.support_reply_prefix')."\n".$message
        );
    }
}
