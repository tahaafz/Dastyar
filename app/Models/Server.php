<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    protected $fillable = [
        'user_id','provider','external_id',
        'plan','region_id','os_image_id','name',
        'login_user','login_pass',
        'ip_address','status','raw_response'
    ];

    protected $casts = [
        'raw_response' => 'array',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
