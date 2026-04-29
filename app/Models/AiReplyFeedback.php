<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiReplyFeedback extends Model
{
    protected $table = 'ai_reply_feedback';

    protected $fillable = [
        'store_id',
        'feedback_type',
        'rating',
        'sample_review_comment',
        'generated_reply',
        'category',
        'keywords',
        'customer_type',
        'comment',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public static function goodExamplesFor(int $storeId, int $limit = 3): array
    {
        return static::where('store_id', $storeId)
            ->where('feedback_type', 'good')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn($f) => [
                'review' => $f->sample_review_comment,
                'reply' => $f->generated_reply,
            ])
            ->toArray();
    }

    public static function badExamplesFor(int $storeId, int $limit = 3): array
    {
        return static::where('store_id', $storeId)
            ->where('feedback_type', 'bad')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn($f) => [
                'review' => $f->sample_review_comment,
                'reply' => $f->generated_reply,
                'reason' => $f->comment,
            ])
            ->toArray();
    }
}
