<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryState extends Model
{
    protected $fillable = ['category_id', 'title', 'code', 'price', 'sort', 'active'];
    protected $casts = [
        'sort' => 'string',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
