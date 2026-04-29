<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchasePost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'brand_name',
        'product_name',
        'product_status',
        'rank',
        'category',
        'customer_gender',
        'customer_age',
        'customer_reason',
        'product_condition',
        'accessories',
        'block1_text',
        'block2_text',
        'block3_text',
        'full_text',
        'image_path',
        'wp_post_id',
        'wp_media_id',
        'wp_image_url',
        'google_post_id',
        'google_product_id',
        'wp_status',
        'google_post_status',
        'google_product_status',
        'wp_error',
        'google_post_error',
        'google_product_error',
        'google_photo_name',
        'google_photo_status',
        'google_photo_error',
        'instagram_media_id',
        'instagram_status',
        'instagram_error',
        'facebook_post_id',
        'facebook_status',
        'facebook_error',
        'wp_category_slug',
        'wp_tag_name',
        'custom_hashtags',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function isWpPublished(): bool
    {
        return $this->wp_status === 'published';
    }

    public function isGooglePostPublished(): bool
    {
        return $this->google_post_status === 'published';
    }

    public function isFullyPublished(): bool
    {
        return $this->isWpPublished() && $this->isGooglePostPublished();
    }
}
