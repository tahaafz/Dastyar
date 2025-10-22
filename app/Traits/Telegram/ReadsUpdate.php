<?php

namespace App\Traits\Telegram;

trait ReadsUpdate
{
    public function handle(array $u): void
    {
        if ($data = data_get($u, 'callback_query.data')) {
            $this->onCallback($data, $u);
            return;
        }

        if ($photos = data_get($u, 'message.photo')) {
            $this->onPhoto($photos, $u);
            return;
        }

        if ($doc = data_get($u, 'message.document')) {
            $this->onDocument($doc, $u);
            return;
        }

        $text = data_get($u, 'message.text');
        if ($text !== null && $text !== '') {
            if (method_exists($this, 'interceptShortcuts') && $this->interceptShortcuts($text)) {
                return;
            }

            $this->onText($text, $u);
            return;
        }

        $this->onUnknown($u);
    }

    protected function onText(string $text, array $u): void {}
    protected function onCallback(string $data, array $u): void {}
    protected function onPhoto(array $photos, array $u): void {}
    protected function onDocument(array $doc, array $u): void {}
    protected function onUnknown(array $u): void {}
}
