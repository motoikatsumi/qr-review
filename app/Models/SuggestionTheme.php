<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuggestionTheme extends Model
{
    protected $fillable = ['category_id', 'icon', 'label', 'keyword', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(SuggestionCategory::class, 'category_id');
    }
}
