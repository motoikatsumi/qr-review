<?php

namespace App\Mail;

use App\Models\Review;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowRatingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $review;
    public $store;

    public function __construct(Review $review, Store $store)
    {
        $this->review = $review;
        $this->store = $store;
    }

    public function build()
    {
        $stars = str_repeat('★', $this->review->rating) . str_repeat('☆', 5 - $this->review->rating);

        return $this->subject("【低評価口コミ通知】{$this->store->name} - {$stars}")
                    ->view('emails.low-rating')
                    ->with([
                        'storeName' => $this->store->name,
                        'rating' => $this->review->rating,
                        'stars' => $stars,
                        'comment' => $this->review->comment,
                        'createdAt' => $this->review->created_at->format('Y年m月d日 H:i'),
                    ]);
    }
}
