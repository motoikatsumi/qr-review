<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReplyCategory extends Model
{
    protected $fillable = ['name', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function keywords()
    {
        return $this->hasMany(ReplyKeyword::class, 'category_id');
    }

    public function activeKeywords()
    {
        return $this->keywords()->where('is_active', true)->orderBy('sort_order');
    }
}
