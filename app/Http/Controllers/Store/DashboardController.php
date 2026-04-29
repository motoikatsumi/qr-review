<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\GoogleReview;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user  = Auth::user();
        $store = $user->isAdmin() ? null : $user->store;

        if (!$store) {
            // 管理者がアクセスした場合は店舗一覧にリダイレクト
            return redirect('/admin/stores');
        }

        $reviewCount       = Review::where('store_id', $store->id)->count();
        $googleReviewCount = GoogleReview::where('store_id', $store->id)->count();
        $unrepliedCount    = GoogleReview::where('store_id', $store->id)->whereNull('reply_comment')->count();
        $recentReviews     = Review::where('store_id', $store->id)
            ->orderByDesc('created_at')->take(5)->get();

        return view('store.dashboard', compact(
            'store', 'reviewCount', 'googleReviewCount', 'unrepliedCount', 'recentReviews'
        ));
    }
}
