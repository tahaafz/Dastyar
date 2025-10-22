<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id','type','amount','currency','source','balance_before','balance_after','meta'
    ];
    protected $casts = ['meta'=>'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
