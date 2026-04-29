<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReplyCategory extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'business_type_id', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function keywords()
    {
        return $this->hasMany(ReplyKeyword::class, 'category_id');
    }

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function activeKeywords()
    {
        return $this->keywords()->where('is_active', true)->orderBy('sort_order');
    }
}
