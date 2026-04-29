<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleReview;
use App\Models\PurchasePost;
use App\Models\Review;
use App\Models\Store;
use App\Models\StoreIntegration;
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

        // 月別推移（直近12ヶ月）と前月比
        $monthlyReviews = $this->buildMonthlyTrend(Review::class, 'created_at', $storeId, 12);

        // 年別推移（直近5年）と前年比
        $yearlyReviews = $this->buildYearlyTrend(Review::class, 'created_at', $storeId, 5);

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

        // =============================================
        // Google口コミ統計
        // =============================================
        $gQuery = GoogleReview::query();
        if ($storeId) {
            $gQuery->where('store_id', $storeId);
        }

        $gTotalReviews = (clone $gQuery)->count();
        $gAvgRating = (clone $gQuery)->avg('rating');

        // Google口コミ 評価分布
        $gRatingDistribution = GoogleReview::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating')
            ->toArray();
        $gRatingCounts = [];
        for ($i = 1; $i <= 5; $i++) {
            $gRatingCounts[$i] = $gRatingDistribution[$i] ?? 0;
        }

        // Google口コミ 返信率
        $gRepliedCount = GoogleReview::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereNotNull('reply_comment')
            ->count();
        $gReplyRate = $gTotalReviews > 0 ? round($gRepliedCount / $gTotalReviews * 100, 1) : 0;

        // Google口コミ 高評価率
        $gHighRatingCount = GoogleReview::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where('rating', '>=', 4)
            ->count();
        $gHighRatingRate = $gTotalReviews > 0 ? round($gHighRatingCount / $gTotalReviews * 100, 1) : 0;

        // Google口コミ 日別推移（直近30日）
        $gDailyReviews = GoogleReview::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where('reviewed_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(reviewed_at) as date'), DB::raw('count(*) as count'), DB::raw('AVG(rating) as avg_rating'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Google口コミ 月別推移（直近12ヶ月）と前月比
        $gMonthlyReviews = $this->buildMonthlyTrend(GoogleReview::class, 'reviewed_at', $storeId, 12);

        // Google口コミ 年別推移（直近5年）と前年比
        $gYearlyReviews = $this->buildYearlyTrend(GoogleReview::class, 'reviewed_at', $storeId, 5);

        // =============================================
        // 今日やること（Todo 系）
        // =============================================

        // 1. 未返信の Google 口コミ件数
        // 一覧画面と仕様を揃えて、未返信は「1 年以内」のみカウント
        // （古すぎる未返信口コミは実用上カウントしても意味がないため）
        $unrepliedGoogleCount = GoogleReview::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereNull('reply_comment')
            ->where('reviewed_at', '>=', now()->subYear())
            ->count();

        // 2. 低評価（閾値以下）の内部口コミ（過去 7 日分）
        $lowRatingReviewCount = Review::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where('rating', '<=', 3)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // 3. 投稿失敗の買取投稿（要対応）
        $failedPostCount = PurchasePost::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where(function ($q) {
                $q->where('wp_status', 'failed')
                  ->orWhere('google_post_status', 'failed')
                  ->orWhere('google_photo_status', 'failed')
                  ->orWhere('instagram_status', 'failed')
                  ->orWhere('facebook_status', 'failed');
            })
            ->count();

        // 4. 新着口コミ（今日）
        $todayReviewCount = Review::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereDate('created_at', today())
            ->count();

        $todayGoogleReviewCount = GoogleReview::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->whereDate('reviewed_at', today())
            ->count();

        // =============================================
        // 設定完了状況（セットアップチェック）
        // =============================================
        $setupAlerts = [];

        // 店舗の業種が未設定
        $storesWithoutBusinessType = Store::whereNull('business_type_id')
            ->where('is_active', true)
            ->when($storeId, fn($q) => $q->where('id', $storeId))
            ->count();
        if ($storesWithoutBusinessType > 0) {
            $setupAlerts[] = [
                'label' => "業種が未設定の店舗が {$storesWithoutBusinessType} 件あります",
                'url'   => '/admin/stores',
                'icon'  => '🏢',
            ];
        }

        // 通知メール未設定の店舗
        $storesWithoutNotifyEmail = Store::whereNull('notify_email')
            ->orWhere('notify_email', '')
            ->where('is_active', true)
            ->when($storeId, fn($q) => $q->where('id', $storeId))
            ->count();
        if ($storesWithoutNotifyEmail > 0) {
            $setupAlerts[] = [
                'label' => "低評価通知メールが未設定の店舗が {$storesWithoutNotifyEmail} 件あります",
                'url'   => '/admin/stores',
                'icon'  => '📧',
            ];
        }

        // Google マップ URL 未設定
        $storesWithoutGoogleUrl = Store::where(function ($q) {
                $q->whereNull('google_review_url')->orWhere('google_review_url', '');
            })
            ->where('is_active', true)
            ->when($storeId, fn($q) => $q->where('id', $storeId))
            ->count();
        if ($storesWithoutGoogleUrl > 0) {
            $setupAlerts[] = [
                'label' => "Google マップ URL が未設定の店舗が {$storesWithoutGoogleUrl} 件あります",
                'url'   => '/admin/stores',
                'icon'  => '🌐',
            ];
        }

        // 未払い請求書アラート
        $unpaidInvoices = collect();
        try {
            $tenant = \App\Models\Tenant::current();
            if ($tenant) {
                $unpaidInvoices = \App\Models\Invoice::where('tenant_id', $tenant->id)
                    ->whereIn('status', ['sent', 'overdue'])
                    ->orderBy('due_date')
                    ->get();
            }
        } catch (\Throwable $e) {
            // 無視
        }

        return view('admin.dashboard', compact(
            'stores', 'storeId',
            'totalReviews', 'avgRating',
            'ratingCounts', 'statusDistribution',
            'dailyReviews', 'monthlyReviews', 'yearlyReviews',
            'highRatingRate', 'googleRate',
            'genderDistribution', 'ageDistribution',
            'gTotalReviews', 'gAvgRating', 'gRatingCounts',
            'gReplyRate', 'gRepliedCount', 'gHighRatingRate',
            'gDailyReviews', 'gMonthlyReviews', 'gYearlyReviews',
            // 今日やること
            'unrepliedGoogleCount', 'lowRatingReviewCount', 'failedPostCount',
            'todayReviewCount', 'todayGoogleReviewCount',
            'setupAlerts',
            // 請求書
            'unpaidInvoices'
        ));
    }

    /**
     * 直近 N ヶ月の月別件数・平均評価・前月差分を返す
     */
    private function buildMonthlyTrend(string $modelClass, string $dateColumn, ?int $storeId, int $months): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $rows = $modelClass::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where($dateColumn, '>=', $start)
            ->select(
                DB::raw("DATE_FORMAT($dateColumn, '%Y-%m') as ym"),
                DB::raw('count(*) as count'),
                DB::raw('AVG(rating) as avg_rating')
            )
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $result = [];
        $prevCount = null;
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $key = $date->format('Y-m');
            $row = $rows->get($key);
            $count = $row ? (int) $row->count : 0;
            $avg = $row && $row->count > 0 ? (float) $row->avg_rating : null;
            $diff = $prevCount === null ? null : $count - $prevCount;
            $result[] = [
                'label' => $date->format('Y年n月'),
                'short' => $date->format('Y/n'),
                'count' => $count,
                'avg_rating' => $avg,
                'diff' => $diff,
            ];
            $prevCount = $count;
        }
        return $result;
    }

    /**
     * 直近 N 年の年別件数・平均評価・前年差分を返す
     * システム稼働開始年（2026年）より前は表示しない
     */
    private function buildYearlyTrend(string $modelClass, string $dateColumn, ?int $storeId, int $years): array
    {
        $systemStartYear = 2026;
        $currentYear = (int) now()->format('Y');
        $startYear = max($systemStartYear, $currentYear - ($years - 1));
        $start = \Carbon\Carbon::create($startYear, 1, 1)->startOfYear();

        $rows = $modelClass::query()
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->where($dateColumn, '>=', $start)
            ->select(
                DB::raw("YEAR($dateColumn) as y"),
                DB::raw('count(*) as count'),
                DB::raw('AVG(rating) as avg_rating')
            )
            ->groupBy('y')
            ->orderBy('y')
            ->get()
            ->keyBy('y');

        $result = [];
        $prevCount = null;
        for ($year = $startYear; $year <= $currentYear; $year++) {
            $row = $rows->get($year);
            $count = $row ? (int) $row->count : 0;
            $avg = $row && $row->count > 0 ? (float) $row->avg_rating : null;
            $diff = $prevCount === null ? null : $count - $prevCount;
            $result[] = [
                'label' => $year . '年',
                'short' => (string) $year,
                'count' => $count,
                'avg_rating' => $avg,
                'diff' => $diff,
            ];
            $prevCount = $count;
        }
        return $result;
    }
}
