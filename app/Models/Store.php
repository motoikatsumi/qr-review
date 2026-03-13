<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'google_review_url',
        'ludocid',
        'meo_keywords',
        'meo_ratio',
        'notify_email',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
