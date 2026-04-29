<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\GoogleReview;
use App\Services\GeminiService;
use App\Services\GoogleBusinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleReviewController extends Controller
{
    private function getStore()
    {
        $user = Auth::user();
        return $user->isAdmin() ? null : $user->store;
    }

    public function index(Request $request)
    {
        $store = $this->getStore();
        if (!$store) return redirect('/admin/stores');

        $query = GoogleReview::where('store_id', $store->id)->orderByDesc('reviewed_at');

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }
        if ($request->filled('reply_status')) {
            if ($request->reply_status === 'replied') {
                $query->whereNotNull('reply_comment');
            } else {
                $query->whereNull('reply_comment');
            }
        }

        $reviews = $query->paginate(20);
        return view('store.google-reviews.index', compact('store', 'reviews'));
    }

    public function generateReply(Request $request, GeminiService $gemini)
    {
        $store = $this->getStore();
        if (!$store) return response()->json(['error' => '権限がありません'], 403);

        $validated = $request->validate([
            'review_id'     => 'required|exists:google_reviews,id',
            'category'      => 'nullable|string|max:100',
            'keywords'      => 'nullable|array',
            'keywords.*'    => 'string|max:255',
            'customer_type' => 'required|in:new,repeater,unknown',
        ]);

        $review = GoogleReview::where('store_id', $store->id)->findOrFail($validated['review_id']);

        $reply = $gemini->generateReplyComment(
            $store,
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

    public function reply(Request $request, GoogleReview $review, GoogleBusinessService $google)
    {
        $store = $this->getStore();
        if (!$store || $review->store_id !== $store->id) abort(403);

        $validated = $request->validate([
            'reply_comment' => 'required|string|max:4096',
        ]);

        $success = $google->replyToReview($review, $validated['reply_comment']);

        if (!$success) {
            return back()->with('error', '返信の投稿に失敗しました。');
        }

        return back()->with('success', '返信を投稿しました。');
    }
}
