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
     * 確認画面を表示（または修正のためフォームに戻る）
     */
    public function confirm(Request $request, $slug)
    {
        $store = Store::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:2000',
            'is_ai_generated' => 'nullable|boolean',
            'has_google_account' => 'nullable|in:,0,1',
            'gender' => 'nullable|string|max:10',
            'age' => 'nullable|string|max:10',
        ]);

        // 「修正する」ボタンからの戻り
        if ($request->has('_back')) {
            return redirect('/review/' . $slug)
                ->withInput($request->only('rating', 'comment', 'is_ai_generated', 'has_google_account', 'gender', 'age'));
        }

        // 確認画面を表示
        return view('review.confirm', [
            'store' => $store,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'is_ai_generated' => $validated['is_ai_generated'] ?? 0,
            'has_google_account' => $validated['has_google_account'] ?? '',
            'gender' => $validated['gender'] ?? '',
            'age' => $validated['age'] ?? '',
        ]);
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
            'is_ai_generated' => 'nullable|boolean',
            'has_google_account' => 'nullable|in:0,1',
            'gender' => 'nullable|string|max:10',
            'age' => 'nullable|string|max:10',
        ]);

        // Google口コミポリシー違反チェック（高評価・低評価に関わらず全件チェック）
        $gemini = new GeminiService();
        $policyError = $gemini->validateGooglePolicy($validated['comment']);
        
        if ($policyError) {
            return back()
                ->withInput()
                ->withErrors(['comment' => $policyError]);
        }

        // 高評価（4〜5星）→ Googleアカウント有無で分岐
        if ($validated['rating'] >= 4) {
            $hasGoogleAccount = ($validated['has_google_account'] ?? '') === '1';

            if ($hasGoogleAccount) {
                // Googleアカウントあり → Google口コミ誘導
                $review = Review::create([
                    'store_id' => $store->id,
                    'rating' => $validated['rating'],
                    'comment' => $validated['comment'],
                    'ai_generated_text' => !empty($validated['is_ai_generated']) ? $validated['comment'] : null,
                    'status' => 'redirected_to_google',
                    'gender' => $validated['gender'] ?? null,
                    'age' => $validated['age'] ?? null,
                ]);

                return view('review.google-redirect', [
                    'store' => $store,
                    'review' => $review,
                    'aiText' => $validated['comment'],
                ]);
            } else {
                // Googleアカウントなし → DB保存 + メール通知 + サンキューページ
                $review = Review::create([
                    'store_id' => $store->id,
                    'rating' => $validated['rating'],
                    'comment' => $validated['comment'],
                    'ai_generated_text' => !empty($validated['is_ai_generated']) ? $validated['comment'] : null,
                    'status' => 'no_google_account',
                    'gender' => $validated['gender'] ?? null,
                    'age' => $validated['age'] ?? null,
                ]);

                return view('review.thankyou', compact('store'));
            }
        }

        // 低評価（1〜3星）→ DB保存 → メール送信
        $review = Review::create([
            'store_id' => $store->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'ai_generated_text' => !empty($validated['is_ai_generated']) ? $validated['comment'] : null,
            'status' => 'email_sent',
            'gender' => $validated['gender'] ?? null,
            'age' => $validated['age'] ?? null,
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
            'gender'  => 'nullable|string|max:10',
            'age'     => 'nullable|string|max:10',
        ]);

        $gemini = new GeminiService();
        $text = $gemini->generateSuggestion(
            $store->name,
            $validated['keyword'],
            $validated['gender'] ?? '',
            $validated['age'] ?? ''
        );

        if (!$text) {
            return response()->json(['error' => '文章の生成に失敗しました。'], 500);
        }

        return response()->json(['text' => trim($text)]);
    }
}
