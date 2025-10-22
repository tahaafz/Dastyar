<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['slug', 'title_key'];

    public function states(): HasMany
    {
        return $this->hasMany(CategoryState::class)
            ->where('active', true)
            ->orderBy('id');
    }
}
