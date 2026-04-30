<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuggestionTheme extends Model
{
    use SoftDeletes;

    protected $fillable = ['category_id', 'icon', 'label', 'keyword', 'sort_order', 'is_active', 'is_for_low_rating'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_for_low_rating' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(SuggestionCategory::class, 'category_id');
    }
}
