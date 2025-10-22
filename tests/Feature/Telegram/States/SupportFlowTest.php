<?php

use App\DTOs\Telegram\TelegramUpdateDTO;
use App\Enums\SupportTicketType;
use App\Enums\Telegram\StateKey;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Telegram\Admin\AdminInboxRouter;
use App\Telegram\Callback\Action;
use App\Telegram\Callback\CallbackData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('Support: user ticket lifecycle with admin reply', function () {
    $admin = makeAdminUser();

    $kit = tg()->start(StateKey::Welcome);

    $kit->press(__('telegram.buttons.support'))
        ->expectState(StateKey::Support)
        ->expectText('telegram.support.enter');

    $message = sampleSupportMessage();
    $kit->press($message)
        ->expectState(StateKey::Support)
        ->expectText('telegram.support.sent');

    $ticket = SupportTicket::query()->latest('id')->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->user_id)->toBe($kit->user->id)
        ->and($ticket->type)->toBe(SupportTicketType::Question)
        ->and($ticket->message)->toBe($message)
        ->and($ticket->is_answered)->toBeFalse();

    $adminPayload = collect($kit->fake->messages)
        ->firstWhere('chat_id', $admin->telegram_chat_id);

    expect($adminPayload)->not->toBeNull()
        ->and($adminPayload['text'])->toContain((string)$ticket->id)
        ->and($adminPayload['text'])->toContain($message)
        ->and($adminPayload['reply_markup'] ?? null)->not->toBeNull();

    $callbackData = replyCallbackPayload($kit->user, $ticket);
    expect(json_decode($adminPayload['reply_markup'], true)['inline_keyboard'][0][0]['callback_data'])
        ->toBe($callbackData);

    $router = new AdminInboxRouter();

    $replyStart = TelegramUpdateDTO::from(makeReplyStartUpdate($admin, $kit->user, $ticket));
    expect($router->maybeHandle($admin->fresh(), $replyStart))->toBeTrue();

    $replyTarget = $admin->fresh()->tg_data['admin_reply_target'] ?? null;
    expect($replyTarget['user_id'] ?? null)->toBe($kit->user->id);
    expect($replyTarget['ticket_id'] ?? null)->toBe($ticket->id);

    $replyText = sampleAdminReply();
    $replyDto = TelegramUpdateDTO::from(makeAdminTextUpdate($admin, $replyText));
    expect($router->maybeHandle($admin->fresh(), $replyDto))->toBeTrue();

    $ticket->refresh();
    expect($ticket->is_answered)->toBeTrue()
        ->and($ticket->answered_at)->not->toBeNull();

    $answer = SupportTicket::query()->where('reply_to_id', $ticket->id)->first();
    expect($answer)->not->toBeNull()
        ->and($answer->type)->toBe(SupportTicketType::Answer)
        ->and($answer->message)->toBe($replyText)
        ->and($answer->user_id)->toBe($admin->id);

    $userPayload = collect($kit->fake->messages)
        ->where('chat_id', $kit->user->telegram_chat_id)
        ->last();

    expect($userPayload['text'])->toContain(__('telegram.admin.support_reply_prefix'))
        ->and($userPayload['text'])->toContain($replyText)
        ->and($admin->fresh()->tg_data['admin_reply_target'] ?? null)->toBeNull();

    $ackPayload = collect($kit->fake->messages)
        ->where('chat_id', $admin->telegram_chat_id)
        ->last();

    expect($ackPayload['text'])->toBe(__('telegram.admin.reply_sent'));
});

function makeAdminUser(): User
{
    return User::factory()->create([
        'is_admin' => true,
        'telegram_user_id' => 9001,
        'telegram_chat_id' => 9001,
    ]);
}

function sampleSupportMessage(): string
{
    return 'لطفاً مشکل من را بررسی کنید.';
}

function sampleAdminReply(): string
{
    return 'پاسخ تستی برای کاربر';
}

function replyCallbackPayload(User $target, SupportTicket $ticket): string
{
    return CallbackData::build(Action::AdminReplyStart, [
        'user' => $target->id,
        'ticket' => $ticket->id,
    ]);
}

function makeReplyStartUpdate(User $admin, User $target, SupportTicket $ticket): array
{
    return [
        'update_id' => 100,
        'callback_query' => [
            'id' => 'cb-1',
            'from' => [
                'id' => $admin->telegram_user_id,
                'first_name' => 'Admin',
            ],
            'message' => [
                'chat' => [
                    'id' => $admin->telegram_chat_id,
                    'type' => 'private',
                ],
            ],
            'data' => replyCallbackPayload($target, $ticket),
        ],
    ];
}

function makeAdminTextUpdate(User $admin, string $text): array
{
    return [
        'update_id' => 101,
        'message' => [
            'from' => [
                'id' => $admin->telegram_user_id,
                'first_name' => 'Admin',
            ],
            'chat' => [
                'id' => $admin->telegram_chat_id,
                'type' => 'private',
            ],
            'text' => $text,
        ],
    ];
}
