<?php

namespace App\Telegram\States\Admin;

use App\Enums\Telegram\StateKey;
use App\Models\User;
use App\Telegram\Core\AbstractState;
use App\Telegram\UI\Buttons;

use function e;

class Management extends AbstractState
{
    public function onEnter(): void
    {
        if (! $this->ensureAdmin()) {
            return;
        }

        $this->sendT('telegram.admin.management_intro');
    }

    public function onText(string $text, array $update): void
    {
        if (! $this->ensureAdmin()) {
            return;
        }

        if ($this->isCancelCommand($text)) {
            $this->goEnum(StateKey::Welcome);

            return;
        }

        if ($this->interceptShortcuts($text)) {
            return;
        }

        $query = trim($text);

        if ($query === '') {
            $this->sendT('telegram.admin.management_intro');

            return;
        }

        $user = $this->findTargetUser($query);

        if (! $user) {
            $this->send(__('telegram.admin.user_not_found'));

            return;
        }

        $this->send($this->formatUserDetails($user));
    }

    private function ensureAdmin(): ?User
    {
        $record = $this->process();

        if (! $record instanceof User || ! $record->is_admin) {
            $this->goEnum(StateKey::Welcome);

            return null;
        }

        return $record;
    }

    private function isCancelCommand(string $text): bool
    {
        $normalized = trim($text);

        if ($normalized === Buttons::label('cancel')) {
            return true;
        }

        $lower = mb_strtolower($normalized);

        return in_array($lower, ['cancel', '/cancel'], true);
    }

    private function findTargetUser(string $input): ?User
    {
        $identifier = ltrim(trim($input), '@');

        if ($identifier === '') {
            return null;
        }

        $user = User::query()
            ->where('username', $identifier)
            ->first();

        if ($user) {
            return $user;
        }

        if (! ctype_digit($identifier)) {
            return null;
        }

        return User::query()
            ->where('telegram_user_id', $identifier)
            ->orWhere('telegram_chat_id', $identifier)
            ->orWhere('id', $identifier)
            ->first();
    }

    private function formatUserDetails(User $user): string
    {
        $status = $user->is_blocked
            ? __('telegram.admin.user_status.blocked')
            : __('telegram.admin.user_status.active');

        if ($user->is_blocked && $user->blocked_reason) {
            $status .= ' — '.e($user->blocked_reason);
        }

        return __('telegram.admin.user_details', [
            'id' => $user->id,
            'name' => e($user->name ?? '—'),
            'username' => $user->username ? '@'.e($user->username) : '—',
            'telegram_user_id' => $user->telegram_user_id ?? '—',
            'telegram_chat_id' => $user->telegram_chat_id ?? '—',
            'status' => $status,
            'created_at' => $user->created_at?->format('Y-m-d H:i') ?? '—',
            'last_message_at' => $user->last_message_at?->format('Y-m-d H:i') ?? '—',
            'balance' => number_format($user->balance),
        ]);
    }

    protected function defaultReplyKeyboard(): ?array
    {
        return $this->replyKeyboard([[Buttons::label('cancel')]]);
    }
}
