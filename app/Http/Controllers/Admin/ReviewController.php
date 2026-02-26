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

        $reviews = $query->paginate(20);

        return view('admin.reviews.index', compact('reviews', 'stores'));
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
            fputcsv($file, ['ID', '店舗名', '評価', 'コメント', 'AI生成文', 'ステータス', '投稿日時']);

            foreach ($reviews as $review) {
                fputcsv($file, [
                    $review->id,
                    $review->store->name,
                    $review->rating,
                    $review->comment,
                    $review->ai_generated_text,
                    $review->status === 'email_sent' ? 'メール送信済' : 'Google誘導',
                    $review->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
