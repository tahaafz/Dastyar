<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_link_id',
        'title',
        'city',
        'price',
        'link',
        'payload',
    ];

    protected $casts = [
        'user_link_id' => 'integer',
        'payload'      => 'array',
    ];

    public function userLink()
    {
        return $this->belongsTo(UserLink::class);
    }
}
