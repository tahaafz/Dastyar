<?php

namespace App\Services\Telegram\Media;

use Illuminate\Support\Facades\Http;

class TelegramFileDownloader
{
    public function getFilePath(string $fileId): ?string
    {
        $token = config('telegram.bots.mybot.token') ?? env('TELEGRAM_BOT_TOKEN');
        $resp = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getFile", [
            'file_id' => $fileId
        ]);

        if (!$resp->ok()) return null;
        $filePath = data_get($resp->json(), 'result.file_path');
        return $filePath ?: null;
    }

    public function download(string $filePath): ?string
    {
        $token = config('telegram.bots.mybot.token') ?? env('TELEGRAM_BOT_TOKEN');
        $timeout = (int) config('media.download_timeout', 15);

        $url = "https://api.telegram.org/file/bot{$token}/{$filePath}";
        $resp = Http::timeout($timeout)->retry(2, 200)->get($url);

        return $resp->ok() ? $resp->body() : null;
    }
}
