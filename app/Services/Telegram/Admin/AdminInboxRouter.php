<?php

namespace App\Services\Telegram\Admin;

use App\DTOs\Telegram\TelegramUpdateDTO;
use App\Enums\SupportTicketType;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Telegram\TopupApprovalService;
use App\Traits\Telegram\TgApi;
use Illuminate\Support\Carbon;

class AdminInboxRouter
{
    use TgApi;

    public function maybeHandle(User $actor, TelegramUpdateDTO $dto): bool
    {
        if (!$actor->is_admin) {
            return false;
        }

        if ($dto->cbData) {
            if ($p = \App\Telegram\Callback\CallbackData::parse($dto->cbData)) {
                if ($p['action'] === \App\Telegram\Callback\Action::TopupApprove || $p['action'] === \App\Telegram\Callback\Action::TopupReject) {
                    $id = (int)($p['params']['id'] ?? 0);
                    $act = $p['action'] === \App\Telegram\Callback\Action::TopupApprove ? 'approve' : 'reject';
                    app(TopupApprovalService::class)->handle($actor, $act, $id);
                    if ($cbId = data_get($dto->raw,'callback_query.id')) {
                        $this->tgToast(
                            $cbId,
                            $act === 'approve' ? __('telegram.admin.approved') : __('telegram.admin.rejected'),
                            false,
                            1
                        );
                    }
                    return true;
                }
                if ($p['action'] === \App\Telegram\Callback\Action::AdminReplyStart) {
                    $targetUserId = (int)($p['params']['user'] ?? 0);
                    $ticketId = (int)($p['params']['ticket'] ?? 0);

                    $ticket = SupportTicket::query()
                        ->whereKey($ticketId)
                        ->where('user_id', $targetUserId)
                        ->where('type', SupportTicketType::Question)
                        ->first();

                    if (!$ticket) {
                        return false;
                    }

                    $this->rememberReplyTarget($actor, $targetUserId, $ticket->id);
                    $this->tgSend($actor->telegram_chat_id, __('telegram.admin.reply_prompt'));
                    return true;
                }
            }
        }

        if ($dto->text && ($target = $this->replyTarget($actor))) {
            $user = User::find($target['user_id']);
            $ticket = SupportTicket::find($target['ticket_id']);

            if ($ticket && $ticket->type === SupportTicketType::Question) {
                $ticket->markAsAnswered($dto->text, $actor);
            }

            if ($user && $user->telegram_chat_id) {
                $this->tgSend($user->telegram_chat_id, __('telegram.admin.support_reply_prefix')."\n".$dto->text);
                $this->tgSend($actor->telegram_chat_id, __('telegram.admin.reply_sent'));
            }
            $this->forgetReplyTarget($actor);
            return true;
        }

        if (($photos = data_get($dto->raw,'message.photo')) && ($target = $this->replyTarget($actor))) {
            $user = User::find($target['user_id']);
            $ticket = SupportTicket::find($target['ticket_id']);
            $last = $photos[array_key_last($photos)] ?? null;
            $fileId = $last['file_id'] ?? null;

            if ($user && $user->telegram_chat_id && $fileId) {
                if ($ticket && $ticket->type === SupportTicketType::Question) {
                    $ticket->markAsAnswered('[photo] '.$fileId, $actor);
                }

                $this->tgSendPhoto($user->telegram_chat_id, $fileId, __('telegram.admin.support_reply_photo'));
                $this->tgSend($actor->telegram_chat_id, __('telegram.admin.reply_photo_sent'));
            } else {
                $this->tgSend($actor->telegram_chat_id, __('telegram.admin.invalid_photo'));
            }
            $this->forgetReplyTarget($actor);
            return true;
        }

        return false;
    }

    private function rememberReplyTarget(User $actor, int $targetUserId, int $ticketId): void
    {
        if ($targetUserId <= 0 || $ticketId <= 0) {
            return;
        }

        $data = $actor->tg_data ?? [];
        $data['admin_reply_target'] = [
            'user_id' => $targetUserId,
            'ticket_id' => $ticketId,
            'expires_at' => Carbon::now()->addMinutes(10)->toIso8601String(),
        ];

        $actor->forceFill(['tg_data' => $data])->save();
    }

    private function replyTarget(User $actor): ?array
    {
        $data = $actor->tg_data['admin_reply_target'] ?? null;
        if (!$data) {
            return null;
        }

        $userId = (int)($data['user_id'] ?? 0);
        $ticketId = (int)($data['ticket_id'] ?? 0);

        if ($userId <= 0 || $ticketId <= 0) {
            $this->forgetReplyTarget($actor);
            return null;
        }

        $expiresAt = $data['expires_at'] ?? null;
        if ($expiresAt && Carbon::now()->greaterThan(Carbon::parse($expiresAt))) {
            $this->forgetReplyTarget($actor);
            return null;
        }

        return [
            'user_id' => $userId,
            'ticket_id' => $ticketId,
        ];
    }

    private function forgetReplyTarget(User $actor): void
    {
        if (!$actor->tg_data || !array_key_exists('admin_reply_target', $actor->tg_data)) {
            return;
        }

        $data = $actor->tg_data;
        unset($data['admin_reply_target']);

        $actor->forceFill(['tg_data' => $data])->save();
    }
}
