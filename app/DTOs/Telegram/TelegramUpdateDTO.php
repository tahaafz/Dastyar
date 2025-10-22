<?php

namespace App\DTOs\Telegram;

readonly class TelegramUpdateDTO
{
    public function __construct(
        public ?int    $chatId,
        public ?int    $userId,
        public ?string $firstName,
        public ?string $username,
        public ?string $text,
        public ?string $cbData,
        public ?int    $updateId,
        public ?string $chatType,
        public array   $raw,
    ) {}

    public static function from(array $u): self
    {
        $chatId   = data_get($u, 'callback_query.message.chat.id') ?? data_get($u, 'message.chat.id');
        $userId   = data_get($u, 'callback_query.from.id')        ?? data_get($u, 'message.from.id');
        $first    = data_get($u, 'callback_query.from.first_name')?? data_get($u, 'message.from.first_name');
        $uname    = data_get($u, 'callback_query.from.username')  ?? data_get($u, 'message.from.username');
        $text     = data_get($u, 'message.text');
        $cb       = data_get($u, 'callback_query.data');
        $updId    = data_get($u, 'update_id');
        $chatType = data_get($u, 'callback_query.message.chat.type') ?? data_get($u, 'message.chat.type');

        return new self(
            chatId:   $chatId ? (int)$chatId : null,
            userId:   $userId ? (int)$userId : null,
            firstName: $first,
            username: $uname,
            text:     $text,
            cbData:   $cb,
            updateId: $updId ? (int)$updId : null,
            chatType: $chatType,
            raw:      $u,
        );
    }
}
