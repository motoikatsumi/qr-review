<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReplyKeyword extends Model
{
    use SoftDeletes;

    protected $fillable = ['category_id', 'label', 'keyword', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ReplyCategory::class, 'category_id');
    }
}
