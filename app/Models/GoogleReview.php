<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleReview extends Model
{
    protected $fillable = [
        'store_id',
        'google_review_id',
        'reviewer_name',
        'reviewer_photo_url',
        'rating',
        'comment',
        'reply_comment',
        'replied_at',
        'reviewed_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'replied_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeUnreplied($query)
    {
        return $query->whereNull('reply_comment');
    }

    public function scopeReplied($query)
    {
        return $query->whereNotNull('reply_comment');
    }
}
