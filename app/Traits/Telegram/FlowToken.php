<?php

namespace App\Traits\Telegram;

use Illuminate\Support\Str;

trait FlowToken
{
    use TgApi;

    protected function newFlow(int $length = 6, string $key = 'flow_id'): string
    {
        $length = max(1, min(32, $length));

        $state = $this->process();
        $data = (array) ($state->tg_data ?? []);

        $token = $this->makeToken($length);

        $data[$key] = $token;
        $state->forceFill(['tg_data' => $data])->save();

        return $token;
    }

    protected function flow(): string
    {
        $state = $this->process();
        $data = (array) ($state->tg_data ?? []);

        if (isset($data['flow_id']) && is_string($data['flow_id'])) {
            return $data['flow_id'];
        }

        return $this->newFlow();
    }

    protected function pack(string $payload): string
    {
        return 'f:'.$this->flow().'|'.$payload;
    }

    protected function invalidateUI(array $update, ?string $note = null): void
    {
        $note = $note ?? __('telegram.errors.request_expired');
        if ($id = data_get($update, 'callback_query.id')) {
            $this->tgToast($id, __('telegram.errors.request_expired_short'), false, 3);
        }
        $chatId    = data_get($update, 'callback_query.message.chat.id');
        $messageId = data_get($update, 'callback_query.message.message_id');
        $oldText   = data_get($update, 'callback_query.message.text');
        if ($chatId && $messageId && $oldText) {
            $this->tgEdit($chatId, (int)$messageId, $oldText."\n\n".$note, ['inline_keyboard'=>[]]);
        }
    }

    protected function validateCallback(string $data, array $update): array
    {
        if (!preg_match('~^f:([A-Z0-9]{6})\|(.*)$~', $data, $m)) {
            $this->invalidateUI($update);
            return [false, null];
        }
        [$token, $rest] = [$m[1], $m[2]];
        $current = $this->flow();
        if ($token !== $current) {
            $this->invalidateUI($update);
            return [false, null];
        }
        return [true, $rest];
    }

    private function makeToken(int $length): string
    {
        // 0-9 A-Z without confusing characters
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $maxIndex = strlen($alphabet) - 1;

        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $alphabet[random_int(0, $maxIndex)];
        }

        return $token;
    }
}
