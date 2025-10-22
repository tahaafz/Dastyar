<?php

namespace App\Services\Telegram\Media;

use App\Models\MediaFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaStorage
{
    public function storeTelegramPhoto(array $opts): ?MediaFile
    {
        /**
         * $opts = [
         *   'user_id' => ?int,
         *   'mediable' => [ $model, 'purpose' => 'receipt' ],
         *   'tg_file_id' => string,
         *   'tg_unique_id' => ?string,
         *   'tg_file_path' => ?string,
         * ];
         */
        $disk     = config('media.disk', 'media');
        $baseDir  = trim(config('media.base_dir','telegram'), '/');
        $purpose  = $opts['mediable']['purpose'] ?? null;

        $dir = $baseDir . '/' . ($purpose ?: 'misc') . '/' . now()->format('Y/m/d');

        // اگر tg_file_path نداریم، فعلاً نام را براساس unique یا random بگذار
        $basename = $opts['tg_unique_id'] ?? Str::random(16);
        $filename = $basename . '.jpg'; // بیشتر رسیدها photo/jpeg هستند؛ در صورت نیاز MIME detect کن
        $path     = "{$dir}/{$filename}";

        // ذخیره
        Storage::disk($disk)->put($path, $opts['binary'], 'private');

        $size  = Storage::disk($disk)->size($path);
        $sha1  = sha1($opts['binary']);

        /** @var MediaFile $m */
        $m = new MediaFile([
            'user_id'       => $opts['user_id'] ?? null,
            'source'        => 'telegram',
            'driver'        => $disk,
            'dir'           => $dir,
            'filename'      => $filename,
            'path'          => $path,
            'mime'          => 'image/jpeg',
            'size'          => $size,
            'hash_sha1'     => $sha1,
            'tg_file_id'    => $opts['tg_file_id'] ?? null,
            'tg_unique_id'  => $opts['tg_unique_id'] ?? null,
            'tg_file_path'  => $opts['tg_file_path'] ?? null,
            'purpose'       => $purpose,
        ]);

        // اتصال polymorphic
        if (!empty($opts['mediable']['model'])) {
            $model = $opts['mediable']['model'];
            $m->mediable()->associate($model);
        }

        $m->save();
        return $m;
    }
}
