<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LinkResult;

class UserLink extends Model
{
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE   = 'active';

    protected $fillable = [
        'user_id',
        'type',
        'url',
        'status',
        'duration',
        'active_at',
        'expires_at',
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'active_at'  => 'datetime',
        'expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function linkResults()
    {
        return $this->hasMany(LinkResult::class);
    }
}
