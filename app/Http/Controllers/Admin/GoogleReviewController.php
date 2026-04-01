<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleReview;
use App\Models\ReplyCategory;
use App\Models\Store;
use App\Services\GeminiService;
use App\Services\GoogleBusinessService;
use Illuminate\Http\Request;

class GoogleReviewController extends Controller
{
    /**
     * Google口コミ一覧
     */
    public function index(Request $request)
    {
        $stores = Store::where('is_active', true)->orderBy('name')->get();
        $categories = ReplyCategory::with(['keywords' => function ($q) {
            $q->where('is_active', true)->orderBy('sort_order');
        }])->where('is_active', true)->orderBy('sort_order')->get();

        $query = GoogleReview::with('store')->orderByDesc('reviewed_at');

        // 店舗フィルター
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        // 星評価フィルター
        if ($request->filled('rating')) {
            $query->where('rating', $request->input('rating'));
        }

        // 返信状態フィルター
        if ($request->input('reply_status') === 'unreplied') {
            $query->unreplied();
        } elseif ($request->input('reply_status') === 'replied') {
            $query->replied();
        }

        $reviews = $query->paginate(20)->appends($request->query());

        return view('admin.google-reviews.index', compact('reviews', 'stores', 'categories'));
    }

    /**
     * 口コミを同期（全店舗 or 指定店舗）
     */
    public function sync(Request $request, GoogleBusinessService $google)
    {
        if (!$google->isConnected()) {
            return redirect('/admin/google-reviews')->with('error', 'Google連携が設定されていません。先にGoogle設定を行ってください。');
        }

        $storeId = $request->input('store_id');
        $totalSynced = 0;

        if ($storeId) {
            $store = Store::findOrFail($storeId);
            if ($store->google_location_name) {
                $totalSynced = $google->syncReviews($store);
            }
        } else {
            $stores = Store::whereNotNull('google_location_name')
                ->where('google_location_name', '!=', '')
                ->where('is_active', true)
                ->get();

            foreach ($stores as $store) {
                $totalSynced += $google->syncReviews($store);
            }
        }

        return redirect('/admin/google-reviews')->with('success', "{$totalSynced}件の口コミを同期しました。");
    }

    /**
     * AI返信文を生成（AJAX）
     */
    public function generateReply(Request $request, GeminiService $gemini)
    {
        $validated = $request->validate([
            'review_id' => 'required|exists:google_reviews,id',
            'category' => 'nullable|string|max:100',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:255',
            'customer_type' => 'required|in:new,repeater,unknown',
        ]);

        $review = GoogleReview::with('store')->findOrFail($validated['review_id']);

        $reply = $gemini->generateReplyComment(
            $review->store->name,
            $review->rating,
            $review->comment ?? '',
            $validated['category'] ?? '',
            $validated['keywords'] ?? [],
            $review->reviewer_name ?? '',
            $validated['customer_type']
        );

        if (!$reply) {
            return response()->json(['error' => 'AI返信文の生成に失敗しました。'], 500);
        }

        return response()->json(['reply' => $reply]);
    }

    /**
     * 口コミに返信を投稿
     */
    public function reply(Request $request, GoogleReview $review, GoogleBusinessService $google)
    {
        $validated = $request->validate([
            'reply_comment' => 'required|string|max:4096',
        ]);

        $success = $google->replyToReview($review, $validated['reply_comment']);

        $redirect = '/admin/google-reviews?' . http_build_query($request->only(['store_id', 'rating', 'reply_status'])) . '#review-' . $review->id;

        if (!$success) {
            return redirect($redirect)->with('error', '返信の投稿に失敗しました。Google連携設定を確認してください。');
        }

        return redirect($redirect)->with('success', '返信を投稿しました。');
    }

    /**
     * 返信を削除
     */
    public function deleteReply(Request $request, GoogleReview $review, GoogleBusinessService $google)
    {
        $success = $google->deleteReply($review);

        $redirect = '/admin/google-reviews?' . http_build_query($request->only(['store_id', 'rating', 'reply_status'])) . '#review-' . $review->id;

        if (!$success) {
            return redirect($redirect)->with('error', '返信の削除に失敗しました。');
        }

        return redirect($redirect)->with('success', '返信を削除しました。');
    }

    /**
     * 一括投稿用：口コミに返信を投稿（AJAX）
     */
    public function bulkReply(Request $request, GoogleReview $review, GoogleBusinessService $google)
    {
        $validated = $request->validate([
            'reply_comment' => 'required|string|max:4096',
        ]);

        $success = $google->replyToReview($review, $validated['reply_comment']);

        return response()->json(['success' => $success]);
    }
}
