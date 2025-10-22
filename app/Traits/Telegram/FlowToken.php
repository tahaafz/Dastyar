<?php

namespace App\Traits\Telegram;

use Illuminate\Support\Str;

trait FlowToken
{
    use TgApi;

    protected function newFlow(): string
    {
        $p = $this->process();
        $d = $p->tg_data ?? [];
        $d['flow_id'] = Str::upper(Str::random(6));
        unset($d['plan'], $d['plan_code'], $d['region_id'], $d['os_image_id'], $d['vm_name'], $d['login_pass']);
        $d['provider'] = $d['provider'] ?? 'gcore';
        $p->tg_data = $d; $p->save();
        return $d['flow_id'];
    }

    protected function flow(): string
    {
        $p = $this->process();
        $d = $p->tg_data ?? [];
        return $d['flow_id'] ?? $this->newFlow();
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
}
