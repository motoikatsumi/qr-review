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
        'google_location_name',
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

    public function googleReviews()
    {
        return $this->hasMany(GoogleReview::class);
    }

    public function purchasePosts()
    {
        return $this->hasMany(PurchasePost::class);
    }

    public function postTemplate()
    {
        return $this->hasOne(StorePostTemplate::class);
    }
}
