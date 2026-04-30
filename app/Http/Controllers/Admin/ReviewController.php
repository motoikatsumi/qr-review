<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Store;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $stores = Store::all();

        $query = Review::with('store')
            ->orderBy('created_at', 'desc');

        // フィルター：店舗
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // フィルター：評価
        if ($request->filled('rating_filter')) {
            if ($request->rating_filter === 'low') {
                $query->where('rating', '<=', 3);
            } elseif ($request->rating_filter === 'high') {
                $query->where('rating', '>=', 4);
            }
        }

        // フィルター：日付範囲（ダッシュボードからの遷移などで投稿日時を絞る）
        if ($request->filled('from_date')) {
            try {
                $query->where('created_at', '>=', \Carbon\Carbon::parse($request->from_date)->startOfDay());
            } catch (\Throwable $e) {}
        }
        if ($request->filled('to_date')) {
            try {
                $query->where('created_at', '<=', \Carbon\Carbon::parse($request->to_date)->endOfDay());
            } catch (\Throwable $e) {}
        }

        $reviews = $query->paginate(50);
        $totalCount = Review::count();

        return view('admin.reviews.index', compact('reviews', 'stores', 'totalCount'));
    }

    public function export(Request $request)
    {
        $query = Review::with('store')->orderBy('created_at', 'desc');

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        if ($request->filled('rating_filter') && $request->rating_filter === 'low') {
            $query->where('rating', '<=', 3);
        }

        $reviews = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reviews_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($reviews) {
            $file = fopen('php://output', 'w');
            // BOM for Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['ID', '店舗名', '評価', '性別', '年代', '新規/リピーター', 'コメント', 'AI生成文', 'ステータス', '投稿日時']);

            foreach ($reviews as $review) {
                fputcsv($file, [
                    $review->id,
                    $review->store->name,
                    $review->rating,
                    $review->gender ?: '',
                    $review->age ? (str_contains((string)$review->age, '代') ? $review->age : $review->age . '代') : '',
                    $review->visit_type ?: '',
                    $review->comment,
                    $review->ai_generated_text,
                    $review->status === 'email_sent' ? 'メール送信済' : ($review->status === 'no_google_account' ? 'Googleアカウント無' : 'Google誘導'),
                    $review->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return redirect('/admin/reviews')->with('success', '口コミを削除しました。');
    }
}
