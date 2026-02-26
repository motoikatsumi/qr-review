<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Review;
use App\Services\GeminiService;
use App\Mail\LowRatingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ReviewController extends Controller
{
    /**
     * レビューフォームを表示
     */
    public function show($slug)
    {
        $store = Store::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('review.form', compact('store'));
    }

    /**
     * レビューを送信・処理
     */
    public function store(Request $request, $slug)
    {
        $store = Store::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:2000',
        ]);

        // 高評価（4〜5星）→ AI生成 → Google誘導
        if ($validated['rating'] >= 4) {
            $gemini = new GeminiService();
            $aiText = $gemini->generateReviewText(
                $store->name,
                $validated['rating'],
                $validated['comment']
            );

            $review = Review::create([
                'store_id' => $store->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
                'ai_generated_text' => $aiText,
                'status' => 'redirected_to_google',
            ]);

            return view('review.google-redirect', [
                'store' => $store,
                'review' => $review,
                'aiText' => $aiText ?? $validated['comment'],
            ]);
        }

        // 低評価（1〜3星）→ DB保存 → メール送信
        $review = Review::create([
            'store_id' => $store->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'ai_generated_text' => null,
            'status' => 'email_sent',
        ]);

        // メール送信
        try {
            Mail::to($store->notify_email)->send(new LowRatingNotification($review, $store));
        } catch (\Exception $e) {
            \Log::error('メール送信エラー: ' . $e->getMessage());
        }

        return view('review.thankyou', compact('store'));
    }

    /**
     * 口コミ提案文をAIで生成（Ajax）
     */
    public function suggest(Request $request, $slug)
    {
        $store = Store::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'keyword' => 'required|string|max:100',
        ]);

        $gemini = new GeminiService();
        $text = $gemini->generateSuggestion($store->name, $validated['keyword']);

        if (!$text) {
            return response()->json(['error' => '文章の生成に失敗しました。'], 500);
        }

        return response()->json(['text' => trim($text)]);
    }
}
