<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopupRequest extends Model
{
    protected $fillable = [
        'user_id','method','amount','currency','status','receipt_file_id','receipt_note','admin_id','approved_at',
    ];
    protected $casts = ['approved_at'=>'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function admin(): BelongsTo { return $this->belongsTo(User::class,'admin_id'); }
    public function receiptMedia() { return $this->belongsTo(\App\Models\MediaFile::class, 'receipt_media_id'); }
}
