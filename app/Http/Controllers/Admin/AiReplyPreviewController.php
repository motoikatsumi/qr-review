<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiReplyFeedback;
use App\Models\ReplyCategory;
use App\Models\Store;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class AiReplyPreviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * プレビュー画面表示
     */
    public function index(Request $request)
    {
        $stores = Store::where('is_active', true)->orderBy('id')->get();
        $categories = ReplyCategory::where('is_active', true)->with(['keywords' => function ($q) {
            $q->where('is_active', true)->orderBy('sort_order');
        }])->orderBy('sort_order')->get();

        $selectedStoreId = $request->input('store_id', $stores->first()?->id);
        $selectedStore = $selectedStoreId ? Store::find($selectedStoreId) : null;

        $feedbackCount = $selectedStore
            ? AiReplyFeedback::where('store_id', $selectedStore->id)
                ->selectRaw('feedback_type, count(*) as cnt')
                ->groupBy('feedback_type')
                ->pluck('cnt', 'feedback_type')
                ->toArray()
            : [];

        return view('admin.ai-reply-preview.index', compact(
            'stores', 'categories', 'selectedStore', 'feedbackCount'
        ));
    }

    /**
     * AI 返信プレビュー生成（AJAX）
     */
    public function generate(Request $request, GeminiService $gemini)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'rating' => 'required|integer|min:1|max:5',
            'sample_review_comment' => 'nullable|string|max:2000',
            'category' => 'nullable|string|max:100',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:255',
            'customer_type' => 'required|in:new,repeater,unknown',
        ]);

        $store = Store::with('businessType')->findOrFail($validated['store_id']);

        $reply = $gemini->generateReplyComment(
            $store,
            $validated['rating'],
            $validated['sample_review_comment'] ?? '',
            $validated['category'] ?? '',
            $validated['keywords'] ?? [],
            'お客様',
            $validated['customer_type']
        );

        if (!$reply) {
            return response()->json(['success' => false, 'error' => 'AI 返信文の生成に失敗しました'], 500);
        }

        return response()->json(['success' => true, 'reply' => $reply]);
    }

    /**
     * フィードバック保存（👍👎）
     */
    public function feedback(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'feedback_type' => 'required|in:good,bad',
            'rating' => 'required|integer|min:1|max:5',
            'sample_review_comment' => 'nullable|string|max:2000',
            'generated_reply' => 'required|string|max:5000',
            'category' => 'nullable|string|max:100',
            'keywords' => 'nullable|array',
            'customer_type' => 'required|in:new,repeater,unknown',
            'comment' => 'nullable|string|max:1000',
        ]);

        AiReplyFeedback::create([
            'store_id' => $validated['store_id'],
            'feedback_type' => $validated['feedback_type'],
            'rating' => $validated['rating'],
            'sample_review_comment' => $validated['sample_review_comment'] ?? null,
            'generated_reply' => $validated['generated_reply'],
            'category' => $validated['category'] ?? null,
            'keywords' => !empty($validated['keywords']) ? implode(',', $validated['keywords']) : null,
            'customer_type' => $validated['customer_type'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $validated['feedback_type'] === 'good'
                ? '👍 良い例として保存しました。今後の返信生成に反映されます。'
                : '👎 悪い例として保存しました。今後この書き方は避けるようになります。',
        ]);
    }

    /**
     * フィードバック履歴削除
     */
    public function destroyFeedback(AiReplyFeedback $feedback)
    {
        $feedback->delete();
        return response()->json(['success' => true]);
    }
}
