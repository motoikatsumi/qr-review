<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuggestionCategory extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'business_type_id', 'sort_order', 'is_active', 'is_for_low_rating'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_for_low_rating' => 'boolean',
    ];

    public function themes()
    {
        return $this->hasMany(SuggestionTheme::class, 'category_id');
    }

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function activeThemes()
    {
        return $this->themes()->where('is_active', true)->orderBy('sort_order');
    }
}
