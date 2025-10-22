<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaFile extends Model
{
    protected $fillable = [
        'user_id','mediable_id','mediable_type','source','driver',
        'dir','filename','path','mime','size','width','height','hash_sha1',
        'tg_file_id','tg_unique_id','tg_file_path','purpose',
    ];

    protected $casts = [];

    public function mediable(): MorphTo { return $this->morphTo(); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function fullPath(): ?string
    {
        return $this->path ?: ( ($this->dir && $this->filename) ? "{$this->dir}/{$this->filename}" : null );
    }
}
