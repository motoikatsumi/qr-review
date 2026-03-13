<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Review;
use App\Models\SuggestionCategory;
use App\Services\GeminiService;
use App\Mail\LowRatingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    /**
     * レビューフォームを表示
     */
    public function show($slug)
    {
        $store = Store::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // 投稿済みの場合はサンキューページへ
        if (session('review_submitted_' . $slug)) {
            return redirect('/review/' . $slug . '/thankyou');
        }

        $suggestionCategories = SuggestionCategory::where('is_active', true)
            ->with(['activeThemes'])
            ->orderBy('sort_order')
            ->get();

        return view('review.form', compact('store', 'suggestionCategories'));
    }

    /**
     * サンキューページを表示
     */
    public function thankyou($slug)
    {
        $store = Store::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('review.thankyou', compact('store'));
    }

    /**
     * 確認画面を表示（または修正のためフォームに戻る）
     */
    public function confirm(Request $request, $slug)
    {
        $store = Store::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // 投稿済みの場合はサンキューページへ
        if (session('review_submitted_' . $slug)) {
            return redirect('/review/' . $slug . '/thankyou');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:2000',
            'is_ai_generated' => 'nullable|boolean',
            'gender' => 'nullable|string|max:10',
            'age' => 'nullable|string|max:10',
        ]);

        // 「修正する」ボタンからの戻り
        if ($request->has('_back')) {
            return redirect('/review/' . $slug)
                ->withInput($request->only('rating', 'comment', 'is_ai_generated', 'gender', 'age'));
        }

        // 確認画面用の二重送信防止トークン
        $submitToken = Str::random(40);
        session(['review_submit_token' => $submitToken]);

        // 確認画面を表示
        return view('review.confirm', [
            'store' => $store,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'is_ai_generated' => $validated['is_ai_generated'] ?? 0,
            'gender' => $validated['gender'] ?? '',
            'age' => $validated['age'] ?? '',
            'submitToken' => $submitToken,
        ]);
    }

    /**
     * レビューを送信・処理
     */
    public function store(Request $request, $slug)
    {
        $store = Store::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // 投稿済みの場合はサンキューページへ
        if (session('review_submitted_' . $slug)) {
            return view('review.thankyou', compact('store'));
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:2000',
            'is_ai_generated' => 'nullable|boolean',
            'gender' => 'nullable|string|max:10',
            'age' => 'nullable|string|max:10',
            'submit_token' => 'required|string',
        ]);

        // Google口コミポリシー違反チェック（二重送信防止トークン消費前に実行）
        $gemini = new GeminiService();
        $policyError = $gemini->validateGooglePolicy($validated['comment']);
        
        if ($policyError) {
            return back()
                ->withInput()
                ->withErrors(['comment' => $policyError]);
        }

        // 二重送信防止チェック（ポリシーチェック通過後にトークンを消費）
        $sessionToken = session()->pull('review_submit_token');
        if (!$sessionToken || $sessionToken !== $validated['submit_token']) {
            return view('review.thankyou', compact('store'));
        }

        // 高評価（4〜5星）→ DB保存 + Googleマップ誘導（confirmページで別タブ遷移済み）
        if ($validated['rating'] >= 4) {
            $review = Review::create([
                'store_id' => $store->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
                'ai_generated_text' => !empty($validated['is_ai_generated']) ? $validated['comment'] : null,
                'status' => 'redirected_to_google',
                'gender' => $validated['gender'] ?? null,
                'age' => $validated['age'] ?? null,
            ]);

            session(['review_submitted_' . $slug => true]);
            return view('review.thankyou', compact('store'));
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

        session(['review_submitted_' . $slug => true]);
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
