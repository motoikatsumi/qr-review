<?php

namespace App\Mail;

use App\Models\GoogleReview;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GoogleLowRatingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $review;
    public $store;

    public function __construct(GoogleReview $review, Store $store)
    {
        $this->review = $review;
        $this->store = $store;
    }

    public function build()
    {
        $stars = str_repeat('★', $this->review->rating) . str_repeat('☆', 5 - $this->review->rating);

        return $this->subject("【Google低評価口コミ通知】{$this->store->name} - {$stars}")
                    ->view('emails.google-low-rating')
                    ->with([
                        'storeName' => $this->store->name,
                        'rating' => $this->review->rating,
                        'stars' => $stars,
                        'reviewerName' => $this->review->reviewer_name,
                        'comment' => $this->review->comment,
                        'reviewedAt' => $this->review->reviewed_at->format('Y年m月d日 H:i'),
                    ]);
    }
}
