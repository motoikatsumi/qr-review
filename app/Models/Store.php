<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

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
        'business_type_id',
        'ai_custom_instruction',
        'ai_extra_ng_words',
        'ai_tone_preference',
        'ai_area_keywords',
        'ai_service_keywords',
        'ai_reply_instruction',
        'ai_store_description',
        'custom_hashtags',
        'use_wordpress',
        'ai_reply_length',
        'ai_suggestion_length',
        'notify_threshold',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'use_wordpress' => 'boolean',
    ];

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }

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

    public function integrations()
    {
        return $this->hasMany(StoreIntegration::class);
    }

    public function integration(string $service): ?StoreIntegration
    {
        return $this->integrations()->where('service', $service)->first();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
