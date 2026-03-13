<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuggestionCategory extends Model
{
    protected $fillable = ['name', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function themes()
    {
        return $this->hasMany(SuggestionTheme::class, 'category_id');
    }

    public function activeThemes()
    {
        return $this->themes()->where('is_active', true)->orderBy('sort_order');
    }
}
