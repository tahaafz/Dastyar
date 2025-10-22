<?php

namespace App\Jobs\Telegram;

use App\Models\MediaFile;
use App\Models\User;
use App\Services\Telegram\Media\MediaStorage;
use App\Services\Telegram\Media\TelegramFileDownloader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ArchiveTelegramPhotoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int     $userId,
        public string  $tgFileId,
        public ?string $tgUniqueId,
        public ?string $purpose,
        public string  $mediableType,
        public int     $mediableId
    ) {}

    public function handle(TelegramFileDownloader $tg, MediaStorage $storage): void
    {
        $path = $tg->getFilePath($this->tgFileId);
        if (!$path) return;

        $binary = $tg->download($path);
        if (!$binary) return;

        $model = app($this->mediableType)::find($this->mediableId);
        if (!$model) return;

        $opts = [
            'user_id'  => $this->userId,
            'mediable' => [ 'model' => $model, 'purpose' => $this->purpose ],
            'tg_file_id'   => $this->tgFileId,
            'tg_unique_id' => $this->tgUniqueId,
            'tg_file_path' => $path,
            'binary'       => $binary,
        ];

        $media = $storage->storeTelegramPhoto($opts);

        if (method_exists($model, 'getTable') && $model->getTable() === 'topup_requests') {
            $model->receipt_media_id = $media?->id;
            $model->save();
        }
    }
}
