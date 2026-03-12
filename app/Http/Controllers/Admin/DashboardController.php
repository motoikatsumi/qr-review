<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $storeId = $request->input('store_id');
        $stores = Store::orderBy('id')->get();

        $query = Review::query();
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        // 基本統計
        $totalReviews = $query->count();
        $avgRating = $query->avg('rating');

        // 評価分布
        $ratingDistribution = Review::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // 1〜5の全評価を埋める
        $ratingCounts = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingCounts[$i] = $ratingDistribution[$i] ?? 0;
        }

        // ステータス分布
        $statusDistribution = Review::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 日別推移（直近30日）
        $dailyReviews = Review::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'), DB::raw('AVG(rating) as avg_rating'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 高評価率
        $highRatingCount = Review::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where('rating', '>=', 4)
            ->count();
        $highRatingRate = $totalReviews > 0 ? round($highRatingCount / $totalReviews * 100, 1) : 0;

        // Google誘導率
        $googleRedirectCount = Review::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where('status', 'redirected_to_google')
            ->count();
        $googleRate = $totalReviews > 0 ? round($googleRedirectCount / $totalReviews * 100, 1) : 0;

        // 性別分布
        $genderDistribution = Review::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereNotNull('gender')
            ->where('gender', '!=', '')
            ->select('gender', DB::raw('count(*) as count'))
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();

        // 年代分布
        $ageDistribution = Review::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereNotNull('age')
            ->where('age', '!=', '')
            ->select('age', DB::raw('count(*) as count'))
            ->groupBy('age')
            ->orderBy('age')
            ->pluck('count', 'age')
            ->toArray();

        return view('admin.dashboard', compact(
            'stores', 'storeId',
            'totalReviews', 'avgRating',
            'ratingCounts', 'statusDistribution',
            'dailyReviews', 'highRatingRate', 'googleRate',
            'genderDistribution', 'ageDistribution'
        ));
    }
}
