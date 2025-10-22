<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name', 'username', 'email', 'password',
        'telegram_user_id', 'telegram_chat_id',
        'tg_current_state', 'tg_data', 'tg_last_message_id',
        'is_admin',
        'is_blocked', 'blocked_reason', 'message_count', 'last_message_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'tg_data' => 'array',
        'last_message_at' => 'datetime',
    ];

    public function servers()
    {
        return $this->hasMany(\App\Models\Server::class);
    }
    public function topups() { return $this->hasMany(\App\Models\TopupRequest::class); }
    public function walletTransactions() { return $this->hasMany(\App\Models\WalletTransaction::class); }
}
